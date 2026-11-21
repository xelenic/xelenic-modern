/*
 * Minimal zip support with no dependencies.
 *   Zip.read(arrayBuffer)  -> Promise<{ "path/in/zip": Uint8Array }>  (stored + deflate entries)
 *   Zip.write({ path: string | Uint8Array }) -> Blob                   (stored entries, UTF-8 names)
 * Deflate is decoded with the browser's DecompressionStream('deflate-raw').
 */
const Zip = (function () {
  const enc = new TextEncoder();
  const dec = new TextDecoder();
  let table = null;

  function crc32(bytes) {
    if (!table) {
      table = new Uint32Array(256);
      for (let n = 0; n < 256; n++) {
        let c = n;
        for (let k = 0; k < 8; k++) c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
        table[n] = c >>> 0;
      }
    }
    let crc = 0xffffffff;
    for (let i = 0; i < bytes.length; i++) crc = table[(crc ^ bytes[i]) & 0xff] ^ (crc >>> 8);
    return (crc ^ 0xffffffff) >>> 0;
  }

  function toBytes(v) {
    if (typeof v === 'string') return enc.encode(v);
    if (v instanceof Uint8Array) return v;
    return new Uint8Array(v);
  }

  function write(files) {
    const parts = [];
    const central = [];
    const names = Object.keys(files);
    const now = new Date();
    const dosTime = ((now.getHours() << 11) | (now.getMinutes() << 5) | (now.getSeconds() >> 1)) & 0xffff;
    const dosDate = (((now.getFullYear() - 1980) << 9) | ((now.getMonth() + 1) << 5) | now.getDate()) & 0xffff;
    let offset = 0;
    names.forEach(function (name) {
      const data = toBytes(files[name]);
      const nameBytes = enc.encode(name);
      const crc = crc32(data);
      const local = new DataView(new ArrayBuffer(30));
      local.setUint32(0, 0x04034b50, true);
      local.setUint16(4, 20, true);
      local.setUint16(6, 0x0800, true);
      local.setUint16(8, 0, true);
      local.setUint16(10, dosTime, true);
      local.setUint16(12, dosDate, true);
      local.setUint32(14, crc, true);
      local.setUint32(18, data.length, true);
      local.setUint32(22, data.length, true);
      local.setUint16(26, nameBytes.length, true);
      local.setUint16(28, 0, true);
      parts.push(new Uint8Array(local.buffer), nameBytes, data);
      const cd = new DataView(new ArrayBuffer(46));
      cd.setUint32(0, 0x02014b50, true);
      cd.setUint16(4, 20, true);
      cd.setUint16(6, 20, true);
      cd.setUint16(8, 0x0800, true);
      cd.setUint16(10, 0, true);
      cd.setUint16(12, dosTime, true);
      cd.setUint16(14, dosDate, true);
      cd.setUint32(16, crc, true);
      cd.setUint32(20, data.length, true);
      cd.setUint32(24, data.length, true);
      cd.setUint16(28, nameBytes.length, true);
      cd.setUint32(42, offset, true);
      central.push(new Uint8Array(cd.buffer), nameBytes);
      offset += 30 + nameBytes.length + data.length;
    });
    const cdSize = central.reduce(function (n, b) { return n + b.length; }, 0);
    const end = new DataView(new ArrayBuffer(22));
    end.setUint32(0, 0x06054b50, true);
    end.setUint16(8, names.length, true);
    end.setUint16(10, names.length, true);
    end.setUint32(12, cdSize, true);
    end.setUint32(16, offset, true);
    return new Blob(parts.concat(central, [new Uint8Array(end.buffer)]), { type: 'application/zip' });
  }

  async function inflate(bytes) {
    if (typeof DecompressionStream === 'undefined') throw new Error('This browser cannot open compressed zip files. Please use a current version of Chrome, Edge, Safari or Firefox.');
    const stream = new Blob([bytes]).stream().pipeThrough(new DecompressionStream('deflate-raw'));
    return new Uint8Array(await new Response(stream).arrayBuffer());
  }

  async function read(buffer) {
    const u8 = new Uint8Array(buffer);
    const dv = new DataView(u8.buffer, u8.byteOffset, u8.byteLength);
    let eocd = -1;
    for (let i = u8.length - 22; i >= Math.max(0, u8.length - 65557); i--) {
      if (dv.getUint32(i, true) === 0x06054b50) { eocd = i; break; }
    }
    if (eocd < 0) throw new Error('This file is not a zip file.');
    const count = dv.getUint16(eocd + 10, true);
    let p = dv.getUint32(eocd + 16, true);
    const out = {};
    for (let n = 0; n < count; n++) {
      if (dv.getUint32(p, true) !== 0x02014b50) throw new Error('The zip file is damaged.');
      const flags = dv.getUint16(p + 8, true);
      const method = dv.getUint16(p + 10, true);
      const size = dv.getUint32(p + 20, true);
      const nameLen = dv.getUint16(p + 28, true);
      const extraLen = dv.getUint16(p + 30, true);
      const commentLen = dv.getUint16(p + 32, true);
      const localOffset = dv.getUint32(p + 42, true);
      const name = dec.decode(u8.subarray(p + 46, p + 46 + nameLen));
      p += 46 + nameLen + extraLen + commentLen;
      if (name.endsWith('/')) continue;
      if (flags & 1) throw new Error('Password-protected zip files are not supported.');
      const start = localOffset + 30 + dv.getUint16(localOffset + 26, true) + dv.getUint16(localOffset + 28, true);
      const raw = u8.subarray(start, start + size);
      if (method === 0) out[name] = raw.slice();
      else if (method === 8) out[name] = await inflate(raw);
      else throw new Error('"' + name + '" uses a compression method this app cannot read.');
    }
    return out;
  }

  function text(bytes) { return dec.decode(bytes); }

  function dataURL(bytes, mime) {
    let s = '';
    for (let i = 0; i < bytes.length; i += 0x8000) s += String.fromCharCode.apply(null, bytes.subarray(i, i + 0x8000));
    return 'data:' + mime + ';base64,' + btoa(s);
  }

  function fromDataURL(url) {
    const comma = url.indexOf(',');
    if (url.slice(0, 5) !== 'data:' || comma < 0) return new Uint8Array(0);
    const head = url.slice(0, comma);
    const body = url.slice(comma + 1);
    if (/;base64$/.test(head)) {
      const bin = atob(body);
      const b = new Uint8Array(bin.length);
      for (let i = 0; i < bin.length; i++) b[i] = bin.charCodeAt(i);
      return b;
    }
    return enc.encode(decodeURIComponent(body));
  }

  return { read: read, write: write, text: text, dataURL: dataURL, fromDataURL: fromDataURL, crc32: crc32 };
})();
