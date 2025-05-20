# Battlesnake Requirements and Technical Documentation

## API Overview

Battlesnake is a competitive programming game where participants build web servers that control snake movement through API interactions. The game engine communicates with each snake's server through a series of webhooks, and the server must respond with appropriate moves to navigate the game board, avoid collisions, and survive as long as possible.

## Technical Requirements

### API Endpoints

A Battlesnake server must implement the following webhook endpoints:

1. **GET /** - Provides information about the Battlesnake, including appearance customization
2. **POST /start** - Called when a Battlesnake has been entered into a new game
3. **POST /move** - Called for each turn of the game, requires a response with the next move
4. **POST /end** - Called when a game involving this Battlesnake has ended

### Response Requirements

- All responses must be JSON-encoded with Content-Type header set to `application/json`
- All responses must return HTTP 200 OK status code
- Responses must be sent within the timeout period (typically 500ms)
- The move endpoint must return a valid direction: "up", "down", "left", or "right"

### Performance Considerations

- Response time is critical - must respond within the timeout (usually 500ms)
- Latency between the game engine and snake server affects available computation time
- Servers must handle concurrent games with unique game IDs
- Responses that take too long are considered invalid, potentially eliminating the snake

## Game Rules

### Board and Movement

- The game is played on a rectangular grid with multiple snakes competing
- Snakes move one square per turn in one of four directions: up, down, left, or right
- Moving outside board boundaries results in elimination
- Each snake begins coiled in a single square and stretches to full length in initial moves

### Collisions

1. **Self Collisions**: A snake that collides with its own body is eliminated
2. **Body Collisions**: A snake that collides with another snake's body is eliminated
3. **Head-to-Head Collisions**: When two snake heads collide, the shorter snake is eliminated (or both if equal length)

### Health and Food

- Snakes start with full health (typically 100 points)
- Health decreases by 1 point each turn
- Reaching zero health results in elimination
- Consuming food restores health to maximum
- Consuming food causes the snake to grow longer on the next turn
- Food appears on the board according to game-specific rules

### Hazards

- Some maps contain hazard squares that reduce health by more than 1 point when entered
- Hazard placement depends on the specific map being played

## Competitive Strategy Considerations

### Survival Tactics

- Avoid self-collisions by tracking body positions
- Avoid other snakes' bodies
- Maintain health by seeking food when necessary
- Avoid board edges unless strategically beneficial

### Offensive Tactics

- Use head-to-head collisions when longer than opponents
- Cut off opponents' paths to trap them
- Control central board areas when possible
- Block access to food when advantageous

### Defensive Tactics

- Avoid head-to-head collisions with longer snakes
- Keep tail accessible for future moves
- Create space for evasive maneuvers
- Track opponent positions and predict their moves

## PHP Implementation Considerations

- Use a modern PHP framework or lightweight routing system to handle webhooks
- Implement efficient data structures for board representation
- Consider using caching mechanisms for repeated calculations
- Optimize pathfinding algorithms for performance
- Ensure proper error handling and logging
- Implement concurrency handling for multiple simultaneous games

## Deployment Considerations

- Select hosting with low latency to game engine regions
- Choose an engine region close to your server location
- Ensure server stays active and doesn't sleep during inactivity
- Configure proper timeout handling
- Implement monitoring and logging for performance analysis
