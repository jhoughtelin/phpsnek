# README.md - PHP Battlesnake

A high-performance Battlesnake implementation in PHP with Docker support for easy local development.

## Overview

This project implements a competitive Battlesnake in PHP, using advanced pathfinding algorithms and strategic decision-making to create a snake that performs well in competitions. The implementation follows a modular architecture with clean separation of concerns, making it easy to understand, extend, and customize.

## Features

- **Advanced Pathfinding**: A* and Flood Fill algorithms for intelligent movement
- **Strategic Decision-Making**: Multiple strategies that adapt to game state
- **Performance Optimized**: Fast response times well under the 500ms limit
- **Docker Support**: Easy local development with Docker and Docker Compose
- **Comprehensive Documentation**: Architecture, strategies, and testing guides
- **Modular Design**: Clean separation of concerns for easy customization

## Requirements

- Docker and Docker Compose
- PHP 8.1+ (if running without Docker)
- Composer (if running without Docker)

## Quick Start

1. Clone the repository
2. Start the Docker container:
   ```bash
   docker-compose up -d
   ```
3. Your Battlesnake will be available at `http://localhost:8080`

## Project Structure

```
battlesnake/
├── src/
│   ├── Controllers/       # API endpoint handlers
│   ├── Models/            # Game state representation
│   ├── Services/          # Core logic and algorithms
│   │   ├── PathFinding/   # A* and other pathfinding
│   │   ├── SpaceAnalysis/ # Flood fill and space evaluation
│   │   └── Strategy/      # Decision-making strategies
│   ├── Utils/             # Utility classes
│   └── Config/            # Configuration parameters
├── public/                # Web server entry point
├── tests/                 # Unit and integration tests
├── Dockerfile             # Docker configuration
├── docker-compose.yml     # Docker Compose configuration
├── composer.json          # PHP dependencies
└── docker-entrypoint.sh   # Docker startup script
```

## Strategies

The snake uses multiple strategies that are selected based on the current game state:

1. **Food Strategy**: Seeks food when health is low or in early game
2. **Survival Strategy**: Maximizes available space and avoids dangerous situations
3. **Aggressive Strategy**: Targets smaller snakes when it has a length advantage

## Customization

You can customize the snake's behavior by modifying the `SnakeConfig.php` file, which includes:

- Snake appearance (color, head, tail)
- Strategy weights and thresholds
- Pathfinding parameters
- Performance settings
- Logging configuration

## Testing

See the `testing.md` file for detailed instructions on how to test and validate your snake.

## Documentation

- `requirements.md`: Detailed technical requirements and API specifications
- `high_performance_strategies.md`: Analysis of competitive strategies
- `architecture.md`: System design and component relationships
- `testing.md`: Testing and validation procedures

## License

This project is available under the MIT License.

## Acknowledgements

- Battlesnake for creating an amazing platform for competitive programming
- The Battlesnake community for sharing strategies and insights
