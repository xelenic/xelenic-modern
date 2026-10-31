#!/usr/bin/env node
// Builds the home page's robot from the source model, with no dependencies:
//
//   node scripts/optimize-robot-model.mjs
//
// Reads   resources/models/cute-robot/scene.gltf (+ scene.bin), the original.
// Writes  public/moderning_xelenic/assets/robot/robot.glb, loaded by three-robot.js,
//         public/moderning_xelenic/assets/js/robot-model-data.js, the same model
//         embedded for when index.html is opened straight from disk.
//
// 1. Bakes the node transforms into the vertices.
// 2. Simplifies the dense meshes by collapsing edges, never moving the surface
//    further than MAX_DEVIATION and keeping hard edges hard.
// 3. Merges identical vertices and quantizes (KHR_mesh_quantization, which
//    three.js reads natively, so the browser needs no decoder).

import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const SOURCE = resolve(root, 'resources/models/cute-robot');
const GLB = resolve(root, 'public/moderning_xelenic/assets/robot/robot.glb');
const EMBEDDED = resolve(root, 'public/moderning_xelenic/assets/js/robot-model-data.js');

const option = (name, fallback) => {
  const arg = process.argv.find((a) => a.startsWith(`--${name}=`));
  return arg ? Number(arg.split('=')[1]) : fallback;
};

// How far the simplified surface may stray, as a fraction of the model's
// height. The robot is at most about 1300 device pixels tall, so 1 pixel is
// about 0.00077.
const MAX_DEVIATION = option('deviation', 0.0008);
// How far apart two normals may be when one corner takes over another's.
const MAX_NORMAL_ANGLE = option('normal-angle', 6);
// How far a triangle may tilt in one collapse.
const MAX_TILT = option('tilt', 25);
// Meshes smaller than this are left exactly as they are. On the low-poly body
// each vertex's normal shades a large area, and there's little to save anyway.
const MIN_TRIANGLES = option('min-triangles', 2000);

// ---------------------------------------------------------------------------
// Reading the source
// ---------------------------------------------------------------------------

function readSource() {
  const gltf = JSON.parse(readFileSync(resolve(SOURCE, 'scene.gltf'), 'utf8'));
  const bin = readFileSync(resolve(SOURCE, gltf.buffers[0].uri));

  const read = (index, Type) => {
    const accessor = gltf.accessors[index];
    const view = gltf.bufferViews[accessor.bufferView];
    const size = { SCALAR: 1, VEC3: 3 }[accessor.type];
    const stride = view.byteStride ?? size * Type.BYTES_PER_ELEMENT;
    const start = (view.byteOffset ?? 0) + (accessor.byteOffset ?? 0);
    const out = new Float64Array(accessor.count * size);
    const data = new DataView(bin.buffer, bin.byteOffset, bin.byteLength);
    for (let i = 0; i < accessor.count; i++) {
      for (let c = 0; c < size; c++) {
        const at = start + i * stride + c * Type.BYTES_PER_ELEMENT;
        out[i * size + c] = Type === Float32Array ? data.getFloat32(at, true) : data.getUint16(at, true);
      }
    }
    return out;
  };

  const meshes = [];
  const visit = (nodeIndex, parent) => {
    const node = gltf.nodes[nodeIndex];
    const world = multiply(parent, node.matrix ?? IDENTITY);
    if (node.mesh !== undefined) {
      for (const primitive of gltf.meshes[node.mesh].primitives) {
        const positions = read(primitive.attributes.POSITION, Float32Array);
        const normals = read(primitive.attributes.NORMAL, Float32Array);
        for (let i = 0; i < positions.length; i += 3) {
          transformPoint(world, positions, i);
          transformDirection(world, normals, i);
        }
        meshes.push({
          name: gltf.meshes[node.mesh].name ?? `mesh ${node.mesh}`,
          material: primitive.material,
          positions,
          normals,
          indices: Int32Array.from(read(primitive.indices, Uint16Array)),
        });
      }
    }
    (node.children ?? []).forEach((child) => visit(child, world));
  };
  gltf.scenes[gltf.scene ?? 0].nodes.forEach((node) => visit(node, IDENTITY));

  return { materials: gltf.materials, meshes };
}

const IDENTITY = [1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1];

function multiply(a, b) {
  const out = new Array(16).fill(0);
  for (let c = 0; c < 4; c++) {
    for (let r = 0; r < 4; r++) {
      for (let k = 0; k < 4; k++) out[c * 4 + r] += a[k * 4 + r] * b[c * 4 + k];
    }
  }
  return out;
}

function transformPoint(m, v, i) {
  const [x, y, z] = [v[i], v[i + 1], v[i + 2]];
  v[i] = m[0] * x + m[4] * y + m[8] * z + m[12];
  v[i + 1] = m[1] * x + m[5] * y + m[9] * z + m[13];
  v[i + 2] = m[2] * x + m[6] * y + m[10] * z + m[14];
}

// The source's transforms are rotations and translations only, so normals
// just rotate.
function transformDirection(m, v, i) {
  const [x, y, z] = [v[i], v[i + 1], v[i + 2]];
  v[i] = m[0] * x + m[4] * y + m[8] * z;
  v[i + 1] = m[1] * x + m[5] * y + m[9] * z;
  v[i + 2] = m[2] * x + m[6] * y + m[10] * z;
}

// ---------------------------------------------------------------------------
// Simplifying (quadric-ordered half-edge collapse)
// ---------------------------------------------------------------------------
//
// Vertices sharing a position are one "point" with several corners, one per
// normal: that's what makes an edge look hard. A collapse moves a point onto a
// neighbouring point, so no new positions or normals are ever invented, and
// each surviving corner must find a corner there with nearly the same normal.
// A point on a hard edge can therefore only slide along that edge.

class Heap {
  items = [];

  push(item) {
    const a = this.items;
    a.push(item);
    let i = a.length - 1;
    while (i > 0) {
      const parent = (i - 1) >> 1;
      if (a[parent][0] <= a[i][0]) break;
      [a[parent], a[i]] = [a[i], a[parent]];
      i = parent;
    }
  }

  pop() {
    const a = this.items;
    const top = a[0];
    const last = a.pop();
    if (a.length) {
      a[0] = last;
      let i = 0;
      for (;;) {
        const l = i * 2 + 1;
        const r = l + 1;
        let m = i;
        if (l < a.length && a[l][0] < a[m][0]) m = l;
        if (r < a.length && a[r][0] < a[m][0]) m = r;
        if (m === i) break;
        [a[m], a[i]] = [a[i], a[m]];
        i = m;
      }
    }
    return top;
  }

  get size() {
    return this.items.length;
  }
}

function simplify(mesh, maxDeviation) {
  const { positions, normals, indices } = mesh;
  const cosNormal = Math.cos((MAX_NORMAL_ANGLE * Math.PI) / 180);
  const cosTilt = Math.cos((MAX_TILT * Math.PI) / 180);

  // Group vertices into points by position.
  const pointIds = new Map();
  const pointOf = new Int32Array(positions.length / 3);
  const point = []; // point -> position
  const cornersOf = []; // point -> its vertices
  for (let v = 0; v < pointOf.length; v++) {
    const key = `${Math.round(positions[v * 3] * 1e6)},${Math.round(positions[v * 3 + 1] * 1e6)},${Math.round(positions[v * 3 + 2] * 1e6)}`;
    let id = pointIds.get(key);
    if (id === undefined) {
      id = point.length;
      pointIds.set(key, id);
      point.push([positions[v * 3], positions[v * 3 + 1], positions[v * 3 + 2]]);
      cornersOf.push([]);
    }
    pointOf[v] = id;
    cornersOf[id].push(v);
  }

  // Triangles, dropping any already degenerate.
  const tris = [];
  for (let i = 0; i < indices.length; i += 3) {
    const t = [indices[i], indices[i + 1], indices[i + 2]];
    const [a, b, c] = t.map((v) => pointOf[v]);
    if (a !== b && b !== c && c !== a) tris.push(t);
  }
  const alive = new Uint8Array(tris.length).fill(1);
  const trisAt = point.map(() => new Set());
  tris.forEach((t, id) => t.forEach((v) => trisAt[pointOf[v]].add(id)));

  const pointsOfTri = (id) => tris[id].map((v) => pointOf[v]);
  const faceNormal = (a, b, c) => {
    const ux = b[0] - a[0];
    const uy = b[1] - a[1];
    const uz = b[2] - a[2];
    const vx = c[0] - a[0];
    const vy = c[1] - a[1];
    const vz = c[2] - a[2];
    const n = [uy * vz - uz * vy, uz * vx - ux * vz, ux * vy - uy * vx];
    const length = Math.hypot(n[0], n[1], n[2]);
    return { n: length > 0 ? [n[0] / length, n[1] / length, n[2] / length] : [0, 0, 0], area: length / 2 };
  };

  // Each point's quadric: the area-weighted squared distance to its planes.
  const Q = point.map(() => new Float64Array(10));
  const weight = new Float64Array(point.length);
  const addPlane = (id, n, d, w) => {
    const q = Q[id];
    const [a, b, c] = n;
    q[0] += w * a * a;
    q[1] += w * a * b;
    q[2] += w * a * c;
    q[3] += w * a * d;
    q[4] += w * b * b;
    q[5] += w * b * c;
    q[6] += w * b * d;
    q[7] += w * c * c;
    q[8] += w * c * d;
    q[9] += w * d * d;
  };
  tris.forEach((_, id) => {
    const ids = pointsOfTri(id);
    const { n, area } = faceNormal(point[ids[0]], point[ids[1]], point[ids[2]]);
    const d = -(n[0] * point[ids[0]][0] + n[1] * point[ids[0]][1] + n[2] * point[ids[0]][2]);
    ids.forEach((p) => {
      addPlane(p, n, d, area);
      weight[p] += area;
    });
  });

  const error = (qa, qb, p) => {
    const [x, y, z] = p;
    const q = (i) => qa[i] + qb[i];
    return q(0) * x * x + 2 * q(1) * x * y + 2 * q(2) * x * z + 2 * q(3) * x + q(4) * y * y + 2 * q(5) * y * z + 2 * q(6) * y + q(7) * z * z + 2 * q(8) * z + q(9);
  };

  const neighbours = (p) => {
    const out = new Set();
    for (const id of trisAt[p]) for (const other of pointsOfTri(id)) if (other !== p) out.add(other);
    return out;
  };
  const sharedTris = (p, q) => {
    let count = 0;
    for (const id of trisAt[p]) if (pointsOfTri(id).includes(q)) count++;
    return count;
  };
  const isOnBorder = (p) => {
    for (const n of neighbours(p)) if (sharedTris(p, n) !== 2) return true;
    return false;
  };

  // How far each point's surface may already have strayed.
  const drift = new Float64Array(point.length);
  const stamp = new Int32Array(point.length);
  const heap = new Heap();
  const pushEdge = (u, v) => {
    const cost = error(Q[u], Q[v], point[v]) / Math.max(weight[u] + weight[v], 1e-12);
    heap.push([Math.max(cost, 0), u, v, stamp[u], stamp[v]]);
  };
  point.forEach((_, p) => neighbours(p).forEach((n) => pushEdge(p, n)));

  // The corner at v that each corner at u turns into, or null if any has no
  // close match. Only corners of triangles that survive the collapse count.
  const matchCorners = (u, v) => {
    const map = new Map();
    for (const id of trisAt[u]) {
      const ids = pointsOfTri(id);
      if (ids.includes(v)) continue;
      const corner = tris[id][ids.indexOf(u)];
      if (map.has(corner)) continue;
      let best = -1;
      let bestDot = cosNormal;
      for (const candidate of cornersOf[v]) {
        const dot = normals[corner * 3] * normals[candidate * 3] + normals[corner * 3 + 1] * normals[candidate * 3 + 1] + normals[corner * 3 + 2] * normals[candidate * 3 + 2];
        if (dot >= bestDot) {
          bestDot = dot;
          best = candidate;
        }
      }
      if (best < 0) return null;
      map.set(corner, best);
    }
    return map;
  };

  // Whether moving u onto v keeps the surface within bounds. Returns the
  // resulting drift, or -1 if the move isn't allowed.
  const collapseDrift = (u, v) => {
    const pv = point[v];
    let furthest = 0;
    for (const id of trisAt[u]) {
      const ids = pointsOfTri(id);
      if (ids.includes(v)) continue;
      const before = faceNormal(point[ids[0]], point[ids[1]], point[ids[2]]);
      const after = faceNormal(...ids.map((p) => (p === u ? pv : point[p])));
      if (after.area < 1e-14) return -1;
      if (before.n[0] * after.n[0] + before.n[1] * after.n[1] + before.n[2] * after.n[2] < cosTilt) return -1;
      const a = point[ids[0]];
      const distance = Math.abs(before.n[0] * (pv[0] - a[0]) + before.n[1] * (pv[1] - a[1]) + before.n[2] * (pv[2] - a[2]));
      furthest = Math.max(furthest, distance);
    }
    const total = Math.max(drift[u] + furthest, drift[v]);
    return total <= maxDeviation ? total : -1;
  };

  let remaining = tris.length;
  while (heap.size) {
    const [, u, v, stampU, stampV] = heap.pop();
    if (!trisAt[u].size || !trisAt[v].size) continue;
    if (stampU !== stamp[u] || stampV !== stamp[v]) continue;

    // Open edges stay exactly where they are. Sliding a point along one moves
    // it only within its own flat plane, so the drift check would see nothing
    // while the part's outline lost a corner.
    if (sharedTris(u, v) !== 2 || isOnBorder(u)) continue;
    const shared = 2;
    // Keep the surface's topology: the two points may only share the
    // neighbours opposite the edge.
    const nu = neighbours(u);
    let common = 0;
    for (const n of neighbours(v)) if (nu.has(n)) common++;
    if (common !== shared) continue;

    const nextDrift = collapseDrift(u, v);
    if (nextDrift < 0) continue;
    const corners = matchCorners(u, v);
    if (!corners) continue;

    for (const id of [...trisAt[u]]) {
      const ids = pointsOfTri(id);
      if (ids.includes(v)) {
        alive[id] = 0;
        remaining--;
        ids.forEach((p) => trisAt[p].delete(id));
      } else {
        const slot = ids.indexOf(u);
        tris[id][slot] = corners.get(tris[id][slot]);
        trisAt[u].delete(id);
        trisAt[v].add(id);
      }
    }
    for (let i = 0; i < 10; i++) Q[v][i] += Q[u][i];
    weight[v] += weight[u];
    drift[v] = nextDrift;
    stamp[v]++;
    stamp[u]++;
    neighbours(v).forEach((n) => {
      stamp[n]++;
      neighbours(n).forEach((m) => pushEdge(n, m));
    });
  }

  return {
    ...mesh,
    indices: Int32Array.from(tris.filter((_, id) => alive[id]).flat()),
    simplified: remaining,
  };
}

// ---------------------------------------------------------------------------
// Quantizing and writing
// ---------------------------------------------------------------------------

function build({ materials, meshes }) {
  // One grid across all the meshes, so parts that touch stay touching.
  const min = [Infinity, Infinity, Infinity];
  const max = [-Infinity, -Infinity, -Infinity];
  for (const { positions } of meshes) {
    for (let i = 0; i < positions.length; i++) {
      min[i % 3] = Math.min(min[i % 3], positions[i]);
      max[i % 3] = Math.max(max[i % 3], positions[i]);
    }
  }
  const step = Math.max(max[0] - min[0], max[1] - min[1], max[2] - min[2]) / 65535;

  const chunks = [];
  let offset = 0;
  const bufferViews = [];
  const accessors = [];
  const addView = (bytes, target, byteStride) => {
    bufferViews.push({ buffer: 0, byteOffset: offset, byteLength: bytes.length, target, ...(byteStride ? { byteStride } : {}) });
    const padded = Buffer.alloc((bytes.length + 3) & ~3);
    bytes.copy(padded);
    chunks.push(padded);
    offset += padded.length;
    return bufferViews.length - 1;
  };

  const primitives = meshes.map((mesh) => {
    // Merge vertices that are identical once quantized, numbering them in
    // the order the triangles use them, and drop triangles that collapsed.
    const seen = new Map();
    const position = [];
    const normal = [];
    const indices = [];
    const vertex = (v) => {
      const p = [0, 1, 2].map((c) => Math.round((mesh.positions[v * 3 + c] - min[c]) / step));
      const length = Math.hypot(mesh.normals[v * 3], mesh.normals[v * 3 + 1], mesh.normals[v * 3 + 2]) || 1;
      const n = [0, 1, 2].map((c) => Math.round((mesh.normals[v * 3 + c] / length) * 127));
      const key = `${p}|${n}`;
      let id = seen.get(key);
      if (id === undefined) {
        id = seen.size;
        seen.set(key, id);
        position.push(p);
        normal.push(n);
      }
      return { id, grid: p.join() };
    };
    for (let i = 0; i < mesh.indices.length; i += 3) {
      const [a, b, c] = [mesh.indices[i], mesh.indices[i + 1], mesh.indices[i + 2]].map(vertex);
      if (a.grid !== b.grid && b.grid !== c.grid && c.grid !== a.grid) indices.push(a.id, b.id, c.id);
    }

    const positionBytes = Buffer.alloc(position.length * 8);
    const normalBytes = Buffer.alloc(normal.length * 4);
    const low = [65535, 65535, 65535];
    const high = [0, 0, 0];
    position.forEach((p, i) => {
      p.forEach((value, c) => {
        positionBytes.writeUInt16LE(value, i * 8 + c * 2);
        low[c] = Math.min(low[c], value);
        high[c] = Math.max(high[c], value);
      });
    });
    normal.forEach((n, i) => n.forEach((value, c) => normalBytes.writeInt8(value, i * 4 + c)));
    const indexBytes = Buffer.alloc(indices.length * 2);
    indices.forEach((value, i) => indexBytes.writeUInt16LE(value, i * 2));

    accessors.push({ bufferView: addView(positionBytes, 34962, 8), componentType: 5123, count: position.length, type: 'VEC3', min: low, max: high });
    const POSITION = accessors.length - 1;
    accessors.push({ bufferView: addView(normalBytes, 34962, 4), componentType: 5120, normalized: true, count: normal.length, type: 'VEC3' });
    const NORMAL = accessors.length - 1;
    accessors.push({ bufferView: addView(indexBytes, 34963), componentType: 5123, count: indices.length, type: 'SCALAR' });

    mesh.report = { vertices: position.length, triangles: indices.length / 3 };
    return { attributes: { POSITION, NORMAL }, indices: accessors.length - 1, material: mesh.material };
  });

  const gltf = {
    asset: { version: '2.0', generator: 'scripts/optimize-robot-model.mjs' },
    extensionsUsed: ['KHR_mesh_quantization'],
    extensionsRequired: ['KHR_mesh_quantization'],
    scene: 0,
    scenes: [{ nodes: [0] }],
    // The node turns the quantized grid back into the model's own units.
    nodes: [{ name: 'Robot', mesh: 0, translation: min, scale: [step, step, step] }],
    meshes: [{ name: 'Robot', primitives }],
    materials,
    accessors,
    bufferViews,
    buffers: [{ byteLength: offset }],
  };

  return { gltf, bin: Buffer.concat(chunks) };
}

function writeGlb({ gltf, bin }) {
  const json = Buffer.from(JSON.stringify(gltf));
  const jsonChunk = Buffer.alloc((json.length + 3) & ~3, 0x20);
  json.copy(jsonChunk);

  const header = Buffer.alloc(12);
  header.write('glTF', 0, 'ascii');
  header.writeUInt32LE(2, 4);
  header.writeUInt32LE(12 + 8 + jsonChunk.length + 8 + bin.length, 8);
  const chunkHeader = (length, type) => {
    const out = Buffer.alloc(8);
    out.writeUInt32LE(length, 0);
    out.write(type, 4, 'ascii');
    return out;
  };

  mkdirSync(dirname(GLB), { recursive: true });
  writeFileSync(GLB, Buffer.concat([header, chunkHeader(jsonChunk.length, 'JSON'), jsonChunk, chunkHeader(bin.length, 'BIN\0'), bin]));
}

function writeEmbedded({ gltf, bin }) {
  const embedded = structuredClone(gltf);
  embedded.buffers[0].uri = `data:application/octet-stream;base64,${bin.toString('base64')}`;
  writeFileSync(EMBEDDED, `window.XELENIC_ROBOT_GLTF = ${JSON.stringify(embedded)};\n`);
}

// ---------------------------------------------------------------------------

const source = readSource();

let lowest = Infinity;
let highest = -Infinity;
for (const { positions } of source.meshes) {
  for (let i = 1; i < positions.length; i += 3) {
    lowest = Math.min(lowest, positions[i]);
    highest = Math.max(highest, positions[i]);
  }
}
const maxDeviation = MAX_DEVIATION * (highest - lowest);

const meshes = source.meshes.map((mesh) => {
  const before = mesh.indices.length / 3;
  const out = before >= MIN_TRIANGLES ? simplify(mesh, maxDeviation) : { ...mesh };
  out.before = { vertices: mesh.positions.length / 3, triangles: before };
  return out;
});

const built = build({ materials: source.materials, meshes });
writeGlb(built);
writeEmbedded(built);

const sum = (key, field) => meshes.reduce((n, mesh) => n + mesh[key][field], 0);
for (const mesh of meshes) {
  console.log(`${String(mesh.name).padEnd(12)} ${String(source.materials[mesh.material].name).padEnd(14)} triangles ${String(mesh.before.triangles).padStart(6)} -> ${String(mesh.report.triangles).padStart(6)}   vertices ${String(mesh.before.vertices).padStart(6)} -> ${String(mesh.report.vertices).padStart(6)}`);
}
console.log(`total triangles ${sum('before', 'triangles')} -> ${sum('report', 'triangles')}, vertices ${sum('before', 'vertices')} -> ${sum('report', 'vertices')}`);
console.log(`robot.glb ${(readFileSync(GLB).length / 1024).toFixed(0)} KB`);
