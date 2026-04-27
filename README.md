<div align="center">
# 🎮 NovaArcade  
### 3D Survival Arena Shooter — Browser-Based

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Three.js](https://img.shields.io/badge/Three.js-WebGL-000000?style=for-the-badge&logo=three.js&logoColor=white)](https://threejs.org)
[![PHP](https://img.shields.io/badge/PHP-8+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![TailwindCSS](https://img.shields.io/badge/Tailwind-CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)

---

**A full-stack browser-based 3D survival shooter built using Laravel and Three.js.**  
City-scale combat arena · AI-driven bots · Real-time gameplay · Modular architecture

[🚀 Installation](#installation) · [🎮 Gameplay](#gameplay-flow) · [📂 Structure](#project-structure) · [🔮 Roadmap](#future-roadmap)

</div>

---

# 📖 Table of Contents

- Project Overview  
- Core Features  
- Tech Stack  
- System Architecture  
- Project Structure  
- Installation  
- Gameplay Flow  
- Player Controls  
- Performance Optimizations  
- Testing Strategy  
- Future Roadmap  
- Screenshots  
- Author  

---

# 🌐 Project Overview

**NovaArcade** is a browser-based **3D survival shooter platform** developed using **Laravel 12** and **Three.js**.

The system delivers a real-time combat experience directly inside a web browser — without requiring external game engines or installations.

Players enter a **city-scale 3D combat environment**, engage **AI-controlled bots**, and survive until all threats are eliminated.

NovaArcade demonstrates:

- Full-stack web engineering
- Real-time game logic design
- AI-driven simulation systems
- 3D rendering in browser environments
- Modular scalable architecture

---

# 🎯 Core Features

## 🎮 Gameplay System

- City-scale 3D combat arena  
- Solo gameplay mode (Player vs AI Bots)  
- Real-time shooting system  
- First-person movement controls  
- Health and shield mechanics  
- Damage feedback system  
- Victory condition detection  
- Road-level spawn system  

---

## 🤖 AI Bot System

Bots simulate enemy players using state-based logic.

**Bot States:**
IDLE → PATROL → DETECT → TRACK → ATTACK → DEAD

Bot Features:

- Random patrol movement  
- Player proximity detection  
- Target tracking  
- Shooting response  
- Health management  
- Difficulty scaling  

---

## 🗺️ 3D Environment Engine

- Large city-based environment  
- Road-level spawning  
- Bounding box collision detection  
- Multi-building layout  
- Terrain-safe navigation  
- Map optimization system  

---

## 🧠 Match Lifecycle System
```bash
Match Start
    ↓
Player Spawn
    ↓
Bot Spawn
    ↓
Combat Loop
    ↓
Kill Tracking
    ↓
Victory Detection
    ↓
Result Processing
```
---

## 📊 Player Dashboard

- Match statistics  
- Kill tracking  
- Recent matches  
- Mission tracking  
- Inventory overview  
- Quick match launcher  

---

## 🏆 Leaderboard System

- Score ranking  
- Kill tracking  
- Match history  
- Player ranking  

---

## 🎒 Inventory System

- Weapon storage  
- Equipment tracking  
- Loadout preparation  
- Inventory visualization  

---

## 🖥️ Game HUD

Includes:

- ❤️ Health Bar  
- 🛡️ Shield Bar  
- 🔫 Weapon Display  
- 💥 Ammo Counter  
- 🎯 Crosshair  
- 🗺️ Minimap  
- ⏱️ Zone Timer  
- 💀 Kill Counter  

---

# 🧰 Tech Stack

## Backend

- Laravel 12  
- PHP 8+  
- MySQL  
- Laravel Queues  
- Laravel Sanctum  

---

## Frontend

- Three.js  
- JavaScript (ES6+)  
- WebGL  
- Blade Templates  
- Tailwind CSS  

---

## Game Engine Tools

- GLTFLoader  
- Raycasting Engine  
- Bounding Box Collision  
- Game Tick System  

---

# 🧱 System Architecture
## Frontend Layer:
- Three.js Renderer
- HUD Interface
- Game Logic

## Application Layer:
- Laravel Controllers
- Game Services
- Bot Management
- Match Processing

## Data Layer:
- MySQL Database
- Queue System
- Game State Storage

---

# 📂 Project Structure
```bash
nova_arcade/

app/
├── Http/
├── Models/
├── Services/
├── Jobs/
├── Events/

database/
├── migrations/
├── seeders/

public/
├── assets/
│ ├── models/
│ ├── textures/
│ ├── sounds/
│ └── maps/

├── games/
│ └── survival-arena-3d/

resources/
├── views/
│ ├── dashboard.blade.php
│ ├── game.blade.php
│ ├── lobby.blade.php
│ └── leaderboard.blade.php

routes/
├── web.php
├── api.php
```

---

# ⚙️ Installation

## Prerequisites

- PHP 8+
- Composer
- Node.js
- MySQL

---

## Clone Repository

```bash
git clone https://github.com/Ashika07734/nova_arcade.git
cd nova_arcade
```
## Install Dependencies
```bash
composer install
npm install
```
## Setup Environment
```bash
cp .env.example .env
```
## Update database:
```bash
DB_DATABASE=nova_arcade
DB_USERNAME=root
DB_PASSWORD=
```
## Generate Key
php artisan key:generate
## Run Migrations
php artisan migrate
## Run Queue Worker
php artisan queue:work
## Start Server
php artisan serve

## Visit:
http://127.0.0.1:8000

---
# 🎮 Gameplay Flow
```bash
User Login
    ↓
Dashboard Loads
    ↓
Start Match
    ↓
City Map Loads
    ↓
Player Spawns on Road
    ↓
Bots Spawn
    ↓
Combat Begins
    ↓
Bots Eliminated
    ↓
Victory Triggered
    ↓
Results Saved
```
# 🕹️ Player Controls

| Key | Action |
|-----|--------|
| W | Move Forward |
| S | Move Backward |
| A | Move Left |
| D | Move Right |
| Shift | Sprint |
| Space | Jump |
| Mouse | Aim |
| Left Click | Shoot |

---

# 📈 Performance Optimizations

The system includes several performance-focused design strategies:

- Bounding box collision detection  
- Asset preloading system  
- Scene graph optimization  
- Controlled bot spawning  
- Efficient rendering pipeline  

---

# 🧪 Testing Strategy

The following validation processes ensure system reliability:

- Match lifecycle testing  
- Bot behavior testing  
- Collision detection testing  
- Shooting accuracy validation  
- Spawn safety validation  

---

# 🔮 Future Roadmap

## Phase 1 — Current Features

- ✅ Solo gameplay  
- ✅ AI bot system  
- ✅ City-scale map  
- ✅ Player dashboard  
- ✅ Leaderboard system  

---

## Phase 2 — Planned Enhancements

- ⬜ Multiplayer mode  
- ⬜ Squad-based gameplay  
- ⬜ Advanced weapon system  
- ⬜ Dynamic weather engine  

---

## Phase 3 — Future Vision

- ⬜ Voice chat integration  
- ⬜ Map streaming system  
- ⬜ Advanced AI behaviors  
- ⬜ Ranked matchmaking  

---

# 👩‍💻 Author

<div align="center">

## **Ashika**

Full-Stack Developer  
Game Systems Engineer  
Web Technology Enthusiast  

🔗 GitHub:  
https://github.com/Ashika07734

</div>

---

# 📜 License

This project is developed for:

- Educational purposes  
- Academic demonstrations  
- Research experimentation  

All rights reserved by the author.

---

<div align="center">

⭐ **If you find this project valuable, please consider starring the repository**

---

### **NovaArcade — Where Web Engineering Meets Game Design**

</div>
