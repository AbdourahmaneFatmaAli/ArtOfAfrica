<?php
session_start();
include 'db.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
    header('Location: Index.php');
    exit();
}

// getting the artwork id from URL
$art_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// if id id is not valid, redirect
if ($art_id <= 0) {
    header('Location: discover.php');
    exit();
}

// taking the details of the artwork from the database
try {
    $stmt = $pdo->prepare('
        SELECT a.art_id, a.title, a.story, a.created_at, u.username, u.email
        FROM artworks a
        JOIN users u ON a.user_id = u.user_id
        WHERE a.art_id = ?
    ');
    $stmt->execute([$art_id]);
    $artwork = $stmt->fetch();

    if (!$artwork) {
        header('Location: discover.php');
        exit();
    }

    $date = new DateTime($artwork['created_at']);
    $formattedDate = $date->format('F j, Y');

} catch(Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Build the image URL for loading
$imageUrl = 'discover.php?get_image=1&id=' . $art_id;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VR Gallery - <?php echo htmlspecialchars($artwork['title']); ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            margin:0;
            overflow:hidden;
            font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #000;
        }

        #canvas-container {
            width: 100%;
            height: 100vh;
            position: relative;
        }

        /* Loading Screen */
        #loading-screen {
            position: fixed;
            top:0;
            left:0;
            width:100%;
            height:100%;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            z-index:10000;
            transition: opacity 0.5s ease;
        }

        #loading-screen.hidden {
            opacity:0;
            pointer-events:none;
        }

        .loader {
            width:60px;
            height:60px;
            border:4px solid rgba(255,255,255,0.1);
            border-top:4px solid #d35400;
            border-radius:50%;
            animation:spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform:rotate(0deg); }
            100% { transform:rotate(360deg); }
        }

        .loading-text {
            color:#fff;
            margin-top:20px;
            font-size:1.2rem;
            font-weight:300;
        }

        /* Story Panel */
        #story-panel {
            position:fixed;
            right:20px;
            top:50%;
            transform:translateY(-50%);
            background:rgba(0,0,0,0.5);
            backdrop-filter: blur(10px);
            padding:30px;
            border-radius:15px;
            max-width:350px;
            z-index:1000;
        }

        .story-text {
            color: rgba(255,255,255,0.95);
            line-height:1.8;
            font-size:1rem;
        }

        /* Action Buttons */
        #action-buttons {
            position:fixed;
            top:20px;
            left:20px;
            display:flex;
            gap:15px;
            z-index:1000;
        }

        .action-btn {
            background: rgba(211,84,0,0.9);
            color:white;
            border:none;
            padding:12px 24px;
            border-radius:10px;
            cursor:pointer;
            font-weight:600;
            transition:all 0.3s ease;
            display:flex;
            align-items:center;
            gap:8px;
        }

        .action-btn:hover {
            background: rgba(211,84,0,1);
            transform:translateY(-2px);
            box-shadow:0 5px 20px rgba(211,84,0,0.5);
        }

        @media (max-width:768px) {
            #story-panel {
                right:10px;
                max-width: calc(100% - 20px);
                padding:20px;
            }

            #action-buttons {
                top:10px;
                left:10px;
                flex-direction:column;
            }

            .action-btn {
                padding:10px 18px;
                font-size:0.9rem;
            }
        }
    </style>
</head>
<body>

    <!-- Loading screen showing at first while 3D loads -->
    <div id="loading-screen">
        <div class="loader"></div>
        <div class="loading-text">Loading Virtual Gallery...</div>
    </div>

    <!-- 3D canvas container -->
    <div id="canvas-container"></div>

    <!-- Story Panel -->
    <div id="story-panel">
        <div class="story-text"><?php echo htmlspecialchars($artwork['story']); ?></div>
    </div>

    <!-- Action Buttons -->
    <div id="action-buttons">
        <button class="action-btn" onclick="toggleFullscreen()">
            <span id="fullscreen-icon">⛶</span> Fullscreen
        </button>
        <button class="action-btn" onclick="window.location.href='discover.php'">
            Go Back
        </button>
    </div>

    <!-- Three.js This library lets us create 3D graphics in the browser using WebGL -->
    <!-- We use it for VR gallery, walls, floor, ceiling, lights, and artworks -->
    <!-- Without this, all 3D scenes and camera movements won't work -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

    <script>
        // strat main scene, camera and renderer
        let scene, camera, renderer;
        let artwork;
        let keys = {};
        let moveSpeed = 0.1;

        const artworkData = {
            id: <?php echo $art_id; ?>,
            title: <?php echo json_encode($artwork['title']); ?>,
            imageUrl: <?php echo json_encode($imageUrl); ?>
        };

        function init() {
            // Scene
            scene = new THREE.Scene();
            scene.background = new THREE.Color(0xf5f5f5);
            scene.fog = new THREE.Fog(0xf5f5f5, 10, 50);

            // Camera
            camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.set(0, 1.6, 5); 

            // Renderer
            renderer = new THREE.WebGLRenderer({ antialias: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.shadowMap.enabled = true;
            renderer.shadowMap.type = THREE.PCFSoftShadowMap;
            document.getElementById('canvas-container').appendChild(renderer.domElement);

            // Addig lights
            addLights();

            // Create the gallery walls and floor
            createGallery();

            // Load artwork
            loadArtwork();

            // Add controls
            addEventListeners();

            // Hide loading screen 
            setTimeout(() => {
                document.getElementById('loading-screen').classList.add('hidden');
            }, 1000);

            animate();
        }

        function addLights() {
            const ambient = new THREE.AmbientLight(0xffffff, 0.6);
            scene.add(ambient);

            const directional = new THREE.DirectionalLight(0xffffff, 0.8);
            directional.position.set(5,10,5);
            directional.castShadow = true;
            scene.add(directional);
        }

        // Create gallery walls, floor and ceiling
        function createGallery() {
            const floorGeometry = new THREE.PlaneGeometry(30,30);
            const floorMat = new THREE.MeshStandardMaterial({ color:0xcccccc, roughness:0.8, metalness:0.2 });
            const floor = new THREE.Mesh(floorGeometry,floorMat);
            floor.rotation.x = -Math.PI/2;
            floor.receiveShadow = true;
            scene.add(floor);

            const wallGeometry = new THREE.BoxGeometry(12,6,0.2);
            const wallMaterial = new THREE.MeshStandardMaterial({ color:0xffffff, roughness:0.9 });
            const wall = new THREE.Mesh(wallGeometry, wallMaterial);
            wall.position.set(0,3,-3);
            wall.receiveShadow = true;
            scene.add(wall);
        }

        // Load artwork
        function loadArtwork() {
            const textureLoader = new THREE.TextureLoader();
            textureLoader.load(
                artworkData.imageUrl,
                function(texture) {
                    const artGeom = new THREE.PlaneGeometry(4,3);
                    const artMat = new THREE.MeshStandardMaterial({ map:texture, roughness:0.8 });
                    artwork = new THREE.Mesh(artGeom, artMat);
                    artwork.position.set(0, 2.5, -2.89);
                    scene.add(artwork);
                },
                undefined,
                function(err){ console.error('Error loading artwork texture', err); }
            );
        }

        // Handle user input for moving camera
        function addEventListeners() {
            let isDragging = false;
            let prevMouse = { x:0, y:0 };

            renderer.domElement.addEventListener('mousedown', e=>{ isDragging=true; prevMouse={x:e.clientX,y:e.clientY}; });
            renderer.domElement.addEventListener('mousemove', e=>{
                if(isDragging){
                    const dx = e.clientX - prevMouse.x;
                    const dy = e.clientY - prevMouse.y;
                    camera.rotation.y -= dx*0.003;
                    camera.rotation.x -= dy*0.003;
                    camera.rotation.x = Math.max(-Math.PI/3, Math.min(Math.PI/3, camera.rotation.x));
                    prevMouse={x:e.clientX, y:e.clientY};
                }
            });
            renderer.domElement.addEventListener('mouseup', ()=>{ isDragging=false; });

            window.addEventListener('keydown', e=>{ keys[e.key.toLowerCase()]=true; });
            window.addEventListener('keyup', e=>{ keys[e.key.toLowerCase()]=false; });
            window.addEventListener('resize', ()=>{ camera.aspect = window.innerWidth/window.innerHeight; camera.updateProjectionMatrix(); renderer.setSize(window.innerWidth,window.innerHeight); });
        }

        function handleMovement() {
            const dir = new THREE.Vector3();
            const rot = camera.rotation.y;

            if(keys['w'] || keys['arrowup']) { dir.x -= Math.sin(rot)*moveSpeed; dir.z -= Math.cos(rot)*moveSpeed; }
            if(keys['s'] || keys['arrowdown']) { dir.x += Math.sin(rot)*moveSpeed; dir.z += Math.cos(rot)*moveSpeed; }
            if(keys['a'] || keys['arrowleft']) { dir.x -= Math.cos(rot)*moveSpeed; dir.z += Math.sin(rot)*moveSpeed; }
            if(keys['d'] || keys['arrowright']) { dir.x += Math.cos(rot)*moveSpeed; dir.z -= Math.sin(rot)*moveSpeed; }

            const newX = camera.position.x + dir.x;
            const newZ = camera.position.z + dir.z;
            if(Math.abs(newX)<5) camera.position.x=newX;
            if(newZ>-1 && newZ<4) camera.position.z=newZ;
        }

        function animate() {
            requestAnimationFrame(animate);
            handleMovement();
            renderer.render(scene,camera);
        }

        function toggleFullscreen() {
            const container = document.getElementById('canvas-container');
            if(!document.fullscreenElement){ container.requestFullscreen(); }
            else{ document.exitFullscreen(); }
        }

        window.addEventListener('load', init); 
        // Initilize scene when page loads
    </script>
</body>
</html>
