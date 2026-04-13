// public/games/survival-arena-3d/js/config.js

export const GAME_CONFIG = {
    // Server settings (from window.gameData)
    get matchId() { return window.gameData?.matchId; },
    get userId() { return window.gameData?.userId; },
    get userName() { return window.gameData?.userName; },
    get apiBaseUrl() { return window.gameData?.apiBaseUrl; },
    get wsUrl() { return window.gameData?.wsUrl; },
    get csrfToken() { return window.gameData?.csrfToken; },
    
    // Graphics settings
    graphics: {
        antialias: true,
        shadowsEnabled: true,
        shadowMapSize: 2048,
        pixelRatio: Math.min(window.devicePixelRatio, 2),
        maxFPS: 60,
        
        // Post-processing
        bloom: {
            strength: 1.5,
            radius: 0.4,
            threshold: 0.85
        },
        
        fog: {
            enabled: true,
            color: 0x0a0a1a,
            density: 0.002
        }
    },
    
    // Physics settings
    physics: {
        gravity: -9.81,
        playerSpeed: 5.0,
        sprintMultiplier: 1.5,
        jumpForce: 5.0,
        groundLevel: 0
    },
    
    // Player settings
    player: {
        height: 1.8,
        radius: 0.5,
        maxHealth: 100,
        maxShield: 100,
        eyeHeight: 1.6,
        crouchHeight: 0.9
    },
    
    // Camera settings
    camera: {
        fov: 75,
        near: 0.1,
        far: 1000,
        
        // Third-person
        thirdPerson: {
            distance: 10,
            height: 5,
            smoothness: 0.1
        },
        
        // First-person
        firstPerson: {
            bobAmount: 0.05,
            bobSpeed: 0.18
        }
    },
    
    // Controls
    controls: {
        mouseSensitivity: 0.002,
        invertY: false,
        
        // Key bindings
        keys: {
            forward: 'KeyW',
            backward: 'KeyS',
            left: 'KeyA',
            right: 'KeyD',
            jump: 'Space',
            crouch: 'ControlLeft',
            sprint: 'ShiftLeft',
            reload: 'KeyR',
            interact: 'KeyE',
            weapon1: 'Digit1',
            weapon2: 'Digit2',
            weapon3: 'Digit3',
            toggleCamera: 'KeyV'
        }
    },
    
    // Network settings
    network: {
        updateRate: 20, // Send position updates 20 times per second
        interpolationDelay: 100, // ms
        maxPredictionError: 0.5, // meters
        reconnectAttempts: 3,
        reconnectDelay: 2000 // ms
    },
    
    // Audio settings
    audio: {
        masterVolume: 1.0,
        musicVolume: 0.5,
        sfxVolume: 0.8,
        spatialAudio: true,
        maxDistance: 100
    },
    
    // Asset paths
    assets: {
        models: '/assets/models',
        textures: '/assets/textures',
        sounds: '/assets/sounds',
        images: '/assets/images'
    },
    
    // Debug mode
    debug: {
        enabled: false, // Set to true for development
        showFPS: true,
        showPlayerPosition: true,
        showCollisionBoxes: false,
        showNetworkStats: true
    }
};

export default GAME_CONFIG;