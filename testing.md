# Testing and Validation Guide

This document outlines the steps to test and validate the PHP Battlesnake implementation.

## Local Testing

### 1. Start the Docker Container

```bash
# Build and start the container
docker-compose up -d

# Check if the container is running
docker-compose ps
```

### 2. Test API Endpoints

#### Info Endpoint
```bash
curl http://localhost:8080/
```
Expected response:
```json
{
  "apiversion": "1",
  "author": "BattleSnakePHP",
  "color": "#ff5733",
  "head": "default",
  "tail": "default",
  "version": "1.0.0"
}
```

#### Start Game Endpoint
```bash
curl -X POST http://localhost:8080/start -H "Content-Type: application/json" -d '{
  "game": {
    "id": "test-game-id",
    "ruleset": {
      "name": "standard",
      "settings": {
        "foodSpawnChance": 15,
        "minimumFood": 1,
        "hazardDamagePerTurn": 0
      }
    },
    "timeout": 500
  },
  "turn": 0,
  "board": {
    "height": 11,
    "width": 11,
    "food": [{"x": 5, "y": 5}],
    "hazards": [],
    "snakes": [
      {
        "id": "snake-id-1",
        "name": "Test Snake",
        "health": 100,
        "body": [{"x": 0, "y": 0}, {"x": 0, "y": 0}, {"x": 0, "y": 0}],
        "head": {"x": 0, "y": 0},
        "length": 3,
        "shout": ""
      }
    ]
  },
  "you": {
    "id": "snake-id-1",
    "name": "Test Snake",
    "health": 100,
    "body": [{"x": 0, "y": 0}, {"x": 0, "y": 0}, {"x": 0, "y": 0}],
    "head": {"x": 0, "y": 0},
    "length": 3,
    "shout": ""
  }
}'
```

#### Move Endpoint
```bash
curl -X POST http://localhost:8080/move -H "Content-Type: application/json" -d '{
  "game": {
    "id": "test-game-id",
    "ruleset": {
      "name": "standard",
      "settings": {
        "foodSpawnChance": 15,
        "minimumFood": 1,
        "hazardDamagePerTurn": 0
      }
    },
    "timeout": 500
  },
  "turn": 1,
  "board": {
    "height": 11,
    "width": 11,
    "food": [{"x": 5, "y": 5}],
    "hazards": [],
    "snakes": [
      {
        "id": "snake-id-1",
        "name": "Test Snake",
        "health": 99,
        "body": [{"x": 1, "y": 0}, {"x": 0, "y": 0}, {"x": 0, "y": 0}],
        "head": {"x": 1, "y": 0},
        "length": 3,
        "shout": ""
      }
    ]
  },
  "you": {
    "id": "snake-id-1",
    "name": "Test Snake",
    "health": 99,
    "body": [{"x": 1, "y": 0}, {"x": 0, "y": 0}, {"x": 0, "y": 0}],
    "head": {"x": 1, "y": 0},
    "length": 3,
    "shout": ""
  }
}'
```
Expected response (direction may vary):
```json
{
  "move": "right",
  "shout": "Moving right!"
}
```

#### End Game Endpoint
```bash
curl -X POST http://localhost:8080/end -H "Content-Type: application/json" -d '{
  "game": {
    "id": "test-game-id",
    "ruleset": {
      "name": "standard",
      "settings": {
        "foodSpawnChance": 15,
        "minimumFood": 1,
        "hazardDamagePerTurn": 0
      }
    },
    "timeout": 500
  },
  "turn": 10,
  "board": {
    "height": 11,
    "width": 11,
    "food": [],
    "hazards": [],
    "snakes": []
  },
  "you": {
    "id": "snake-id-1",
    "name": "Test Snake",
    "health": 0,
    "body": [],
    "head": {"x": 0, "y": 0},
    "length": 0,
    "shout": ""
  }
}'
```

### 3. Performance Testing

#### Response Time Testing
```bash
# Install Apache Bench if not already installed
apt-get update && apt-get install -y apache2-utils

# Test response time for 100 requests with 10 concurrent connections
ab -n 100 -c 10 -p move_payload.json -T 'application/json' http://localhost:8080/move
```

Create `move_payload.json` with the same content as the move endpoint test above.

#### Memory Usage
```bash
# Check memory usage of the container
docker stats battlesnake-php --no-stream
```

## Battlesnake Engine Testing

For more comprehensive testing, you can use the Battlesnake CLI tool:

```bash
# Install Battlesnake CLI
npm install -g @battlesnake/cli

# Run a game with your snake
battlesnake play -W 11 -H 11 --name YourSnake --url http://localhost:8080
```

## Validation Checklist

- [ ] Info endpoint returns correct snake configuration
- [ ] Start endpoint accepts game initialization
- [ ] Move endpoint returns valid directions
- [ ] End endpoint accepts game completion
- [ ] Response times are consistently under 500ms
- [ ] Snake avoids walls and self-collisions
- [ ] Snake seeks food when health is low
- [ ] Snake uses space efficiently
- [ ] Snake demonstrates competitive behavior against other snakes
- [ ] Docker container starts and runs without errors
- [ ] Application logs show expected behavior
