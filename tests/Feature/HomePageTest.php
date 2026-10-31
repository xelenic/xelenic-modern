<?php

test('the home page loads the optimized robot, its decoder and the frame watcher before the 3D layers', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('moderning_xelenic\/assets\/robot\/robot.glb', false)
        ->assertSeeInOrder(["libs/meshopt_decoder.js'", "'js/frame-budget.js'", "'js/three-bg.js'", "'js/three-robot.js'", "'js/solution-cards.js'"], false);
});

test('the robot model is a valid binary glTF that the page can decode', function () {
    $path = public_path('moderning_xelenic/assets/robot/robot.glb');
    expect($path)->toBeFile();

    $glb = file_get_contents($path);
    $header = unpack('a4magic/Vversion/Vlength/VjsonLength', $glb);

    expect($header['magic'])->toBe('glTF')
        ->and($header['version'])->toBe(2)
        ->and($header['length'])->toBe(strlen($glb));

    $gltf = json_decode(substr($glb, 20, $header['jsonLength']), true);

    // three.js reads quantization by itself and the page loads the meshopt
    // decoder. Anything else, such as Draco, would leave the hero without a robot.
    expect(array_diff($gltf['extensionsRequired'], ['KHR_mesh_quantization', 'EXT_meshopt_compression']))->toBe([]);

    // The script recolours the body by this material name (three-robot.js).
    expect(array_column($gltf['materials'], 'name'))->toContain('Plastik');
});

test('the source model is kept out of the public folder', function () {
    expect(resource_path('models/cute-robot/scene.gltf'))->toBeFile()
        ->and(public_path('moderning_xelenic/assets/cute_robot_gltf'))->not->toBeDirectory();
});
