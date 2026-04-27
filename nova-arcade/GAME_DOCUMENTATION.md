# Game Documentation

## Current Survival Arena Asset Setup

The game now loads uploaded GLB models directly from `public/assets/models` and falls back to procedural forest pieces when a file is missing.

### Environment Layout

- Forest base: `low_poly_forest.glb`
- Dense tree scatter: `low_poly_trees_free.glb`, `sapling.glb`, `low_poly_tree_scene_free.glb`
- Village core: `seven_dwarfs_cottage.glb`, `House 4.glb`, `center-well.glb`, `chair2.glb`
- Utility props: `fench.glb`, `street light.glb`, `windmill_game_ready.glb`
- Player weapon: `low-poly_aek-971.glb`
- Sidearm: `sig_sauer_p226_x-_five_low-poly.glb`

### Placement Rules

- Houses are grouped in a village clearing on one side of the map.
- Trees are scattered in a dense annulus around the play area.
- The forest map is used as the base layer if it loads successfully.
- All GLB files are auto-fitted to believable world sizes before placement.
- Filenames with spaces are loaded using encoded URLs.

### Asset Expectations

- Keep future models low-poly and centered near the origin.
- Prefer separate GLBs for trees, houses, props, and weapons.
- If a model is oversized, the loader will fit it by bounding box height.

