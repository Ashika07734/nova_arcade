# Survival Arena 3D Documentation

This document explains the current Survival Arena game flow, features, runtime systems, and the main functions that power the experience.

## Overview

Survival Arena 3D is a client-driven battle arena built with Three.js and Laravel. The browser handles the visible gameplay loop, while the backend supplies match state, player data, weapons, and optional sync endpoints.

The gameplay loop is centered around:

- loading a 3D city map or a fallback arena,
- spawning the player and bots,
- running movement, shooting, zone pressure, and HUD updates in real time,
- showing a death or victory screen at match end,
- syncing state to the server in the background when available.

## Main Player Features

- First-person style third-person camera with pointer lock aiming.
- WASD movement, sprint, jump, and manual reload.
- Local shooting with projectile visuals and raycast-based hit detection.
- Health and shield tracking.
- Ammo reserve and automatic reload behavior.
- Safe-zone shrinking with escalating damage.
- Kill feed, minimap, damage flash, hit markers, and end-of-match summary.
- Best-effort server sync so the game still works if the backend tick loop is unavailable.

## Asset and World Setup

The game loads uploaded GLB models from `public/assets/models` and falls back to simplified geometry if the main map cannot load.

### Environment Assets

- Forest base: `low_poly_forest.glb`
- Dense tree scatter: `low_poly_trees_free.glb`, `sapling.glb`, `low_poly_tree_scene_free.glb`
- Village core: `seven_dwarfs_cottage.glb`, `House 4.glb`, `center-well.glb`, `chair2.glb`
- Utility props: `fench.glb`, `street light.glb`, `windmill_game_ready.glb`
- Player weapon: `low-poly_aek-971.glb`
- Sidearm: `sig_sauer_p226_x-_five_low-poly.glb`

### Placement Rules

- The city map is preferred first, with fallback maps checked next.
- Collision boxes from the map data are used to keep the player and camera out of buildings.
- If map loading fails, a procedural fallback arena is created with simple blocks and a grid.
- Models are auto-fitted to believable sizes before placement.
- Filenames with spaces are loaded through encoded URLs.

## Controls

- `W`, `A`, `S`, `D`: move.
- `Shift`: sprint.
- `Space`: jump.
- Mouse movement: look around while pointer lock is active.
- Left click: fire weapon.
- `R`: reload.
- Click the canvas: request pointer lock.

## HUD And Screen Elements

The HUD is updated entirely from the client loop and is responsible for:

- health and shield bars,
- ammo count and reserve ammo,
- kill count,
- alive count,
- weapon name,
- match timer,
- zone timer,
- minimap,
- kill feed,
- damage flash,
- victory and death screens.

## Runtime Flow

1. `init()` starts the bootstrap sequence.
2. `createScene()` builds the renderer, camera, and scene.
3. `loadWorld()` loads the map or fallback environment.
4. `createPlayer()` spawns the player model and places the camera.
5. `createWeapon()` attaches the main weapon model.
6. `initializeWeapons()` pulls weapon metadata from the server when available.
7. `initBotAI()` creates the bot controller.
8. `ensureLocalBots()` guarantees there are enemies client-side.
9. `initZone()` starts safe-zone timing.
10. `animate()` runs the frame loop.

## Main Game Systems

### 1. Scene Setup

`createScene()` initializes the Three.js scene, fog, camera, renderer, and shadow settings. It also clears the game container and mounts the WebGL canvas.

### 2. World Loading

`loadWorld()` attempts the configured city map first and then falls back to other map asset paths. If all assets fail, the game enters fallback mode and builds a simple playable arena.

`buildGuaranteedCollisionBoxes()` converts map collision data into Three.js boxes.

`buildFallbackWorld()` creates the emergency arena environment with lighting, ground, and block buildings.

`countSceneMeshes()` counts visible meshes to help detect whether enough world geometry exists.

`ensureEmergencyVisuals()` adds missing fallback visuals if the scene is too empty.

### 3. Player Setup

`createPlayer()` loads the player character, positions it on a valid spawn, and sets the camera starting angle.

`resolvePlayerSpawn()` finds a valid spawn point from map data or fallback coordinates.

`findDefaultFacing()` returns the initial facing direction for the player camera.

### 4. Weapon Setup

`createWeapon()` loads the rifle model and attaches it to the player.

### 5. Bot AI Setup

`initBotAI()` creates the bot controller and wires the bot callbacks into the game.

`ensureLocalBots()` guarantees that the client spawns the requested enemy count even if the server has no active bot state yet.

### 6. Input Handling

`bindInput()` listens for keyboard, mouse, and reload events.

`bindPointerLock()` requests pointer lock when the game canvas is clicked.

`bindResize()` keeps the renderer and camera sized correctly when the window changes.

`getInputState()` translates pressed keys into a movement state object for the player controller.

### 7. Camera Logic

`updateCamera()` positions the camera behind the player, keeps it outside blocking geometry, and applies the current look direction.

### 8. Combat: Player Shooting

`fireWeapon()` handles firing, recoil timing, ammo consumption, muzzle flash, projectile creation, and local hit detection.

`serverShoot()` sends a best-effort combat event to the backend without blocking gameplay.

`showHitMarker()` flashes the crosshair when a shot hits a bot.

### 9. Combat: Player Damage

`applyDamageToPlayer()` applies shield absorption first, then health damage, and triggers death if health reaches zero.

`showDamageIndicator()` adds the red damage flash overlay element.

### 10. Reload System

`triggerReload()` starts the reload timer, refills the magazine after the delay, and updates the reload indicator.

### 11. Safe Zone

`initZone()` initializes the shrinking zone timers and damage profile.

`updateZone()` shrinks the zone over time and damages the player if they stay outside it.

### 12. Kill Feed

`addKillFeedEntry()` prepends a kill event to the local feed and trims the list to the newest items.

`renderKillFeed()` renders the feed into the DOM and avoids unnecessary refreshes when the feed signature has not changed.

### 13. Win / Loss Flow

`checkWinCondition()` ends the match when no bots remain alive.

`showDeath()` displays the death screen and finalizes end-of-match stats.

`showVictory()` displays the victory screen, finalizes stats, and plays a victory sound.

`applyEndStats()` calculates placement, survival time, and score for the end screen.

`playVictorySound()` sequences a short victory jingle.

### 14. Network Sync

`fetchMatchState()` polls the backend for match updates when a match id exists.

`syncServerState()` merges server bot state, kill feed entries, player health, and match-end state into the local client simulation.

`startNetworkLoops()` starts the periodic state polling loop.

`initializeWeapons()` fetches server weapon metadata and initializes the local weapon state.

`sendPositionUpdate()` posts player position, rotation, velocity, sprint, and crouch state to the backend at a limited rate.

`fetchWithTimeout()` wraps `fetch()` with an abort timer so a stalled request does not freeze the game loop.

### 15. Audio

`playShootSound()` plays the player firing sound.

`playHitSound()` plays the bot death / hit confirmation sound.

`playTone()` is the shared Web Audio helper for short synthesized sounds.

### 16. HUD

`updateHud()` is the central HUD refresh function. It updates timers, resource bars, kill counts, alive counts, the weapon label, the minimap, and the damage flash state.

`updateDamageFlash()` fades the screen-edge damage effect over time.

`formatDuration()` converts seconds into `m:ss` display text.

`setText()` and `setWidth()` are small DOM helpers for HUD elements.

`drawMinimap()` paints the player, bots, zone circle, and facing direction on the minimap canvas.

### 17. Bullet Simulation

`updateBullets()` advances all active bullets and removes finished projectiles.

### 18. Main Loop

`animate()` is the core frame loop. Each frame it:

- updates player movement,
- keeps fallback visuals alive,
- updates the camera,
- runs bot AI,
- advances bullets,
- refreshes the HUD,
- applies zone damage,
- sends position updates,
- renders the scene.

### 19. Loading and Error Handling

`finishLoading()` hides the loading overlay after initialization completes.

`updateLoading()` updates the loading text and progress bar.

`showRendererError()` replaces the game canvas with a fallback message if WebGL is unavailable.

## Bot AI System

The bot logic lives in `public/games/survival-arena-3d/js/services/BotAI.js` and runs entirely on the client.

### Bot AI Features

- Local bot spawning if the server returns no bots.
- Difficulty scaling for easy, medium, and hard.
- Patrol, chase, and engage behavior states.
- Strafe movement during combat.
- Shooting with reaction delay, aim error, and headshot chance.
- Raycast-based hit detection against bots.
- Damage, death, and kill callbacks.
- Optional server synchronization for compatibility.

### BotAI Functions

`BotAI.constructor(scene, options)` sets the scene, difficulty, world size, and callback hooks.

`BotAI.difficultyProfile(difficulty)` returns the profile used to tune movement, accuracy, damage, and cooldowns.

`sync(botStates)` merges server bot states into the local bot set.

`spawnLocalBots(count, spawnPoints)` creates local enemies and places them at valid spawn positions.

`_newAIContext(state)` creates the per-bot runtime AI state.

`update(delta)` advances all living bots each frame.

`_botFireAtPlayer(bot, key, dx, dz, dist)` handles bot muzzle flash, bot sound, miss chance, damage calculation, and the player-hit callback.

`_playBotShot()` plays the bot firing sound effect.

`damageBot(key, damage, headshot)` applies damage from the player and triggers the death callback when a bot dies.

`raycastBots(origin, direction, maxDist)` finds the nearest alive bot hit by a ray fired from the player.

`getAliveCount()` returns the number of living bots.

`getAllBotStates()` returns the position and status of every bot for the minimap and other UI.

`clear()` removes all bots from the scene and resets the bot map.

## Important Backend Data

The game reads the following values from `window.gameData` when they are available:

- `matchId`
- `userId`
- `userName`
- `difficulty`
- `botCount`
- `matchDurationSeconds`
- `matchStartedAt`
- `apiBaseUrl`
- `csrfToken`
- `mapData`

## Match End Scoring

The end screen score is calculated from local match performance:

- kills,
- headshots,
- survival time bonus,
- victory bonus.

The current implementation uses the local client simulation as the primary source of truth so the game remains playable even when backend polling is unavailable.

## Asset Expectations

- Keep future models low-poly and centered near the origin.
- Prefer separate GLBs for trees, houses, props, and weapons.
- If a model is oversized, the loader will fit it by bounding box height.

## How To Run

1. Start the PHP server with `php artisan serve`.
2. Start the frontend build with `npm run dev`.
3. Log in and open the Survival Arena dashboard or matchmaking flow.
4. Start a match and play in the browser.

## File Map

- `public/games/survival-arena-3d/js/main-city.js`: main game bootstrap, movement, combat, HUD, zone, and network sync.
- `public/games/survival-arena-3d/js/services/BotAI.js`: bot behavior, bot combat, and bot state management.
- `public/games/survival-arena-3d/js/entities/`: player, weapon, bullet, and bot entity models.
- `public/games/survival-arena-3d/js/world/`: map loading and collision helpers.
- `resources/views/survival-arena/game.blade.php`: game page shell and `gameData` bootstrap.
- `routes/web.php`: authenticated routes for matchmaking, lobby, play, and results.
- `routes/api.php`: optional API sync routes.

