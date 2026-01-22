#!/bin/bash

# Color codes for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

API_URL="http://localhost:8000/api"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Movie Match - Scenario Testing Script${NC}"
echo -e "${BLUE}========================================${NC}\n"

# Function to check if services are running
check_services() {
    echo -e "${YELLOW}Checking services...${NC}"

    # Check Laravel
    if curl -s http://localhost:8000 > /dev/null 2>&1; then
        echo -e "${GREEN}✓ Laravel API is running${NC}"
    else
        echo -e "${RED}✗ Laravel API is NOT running${NC}"
        echo -e "${YELLOW}  Start with: cd storyline-matcher-api && php artisan serve${NC}"
        exit 1
    fi

    # Check Embedding Service
    if curl -s http://localhost:5001/health > /dev/null 2>&1; then
        echo -e "${GREEN}✓ Embedding Service is running${NC}"
    else
        echo -e "${RED}✗ Embedding Service is NOT running${NC}"
        echo -e "${YELLOW}  Start with: cd embedding-service && python3 app.py${NC}"
        exit 1
    fi

    echo ""
}

# Function to display JSON nicely
display_json() {
    if command -v jq &> /dev/null; then
        echo "$1" | jq '.'
    else
        echo "$1"
    fi
}

# Test Scenario 1: Title-based search
test_scenario_1() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}SCENARIO 1: Search by Title${NC}"
    echo -e "${BLUE}========================================${NC}\n"

    echo -e "${YELLOW}Step 1: Searching for 'Inception'...${NC}"
    SEARCH_RESULT=$(curl -s -X GET "${API_URL}/movies/search?query=Inception" \
        -H "Accept: application/json")

    echo -e "${GREEN}Search Results:${NC}"
    display_json "$SEARCH_RESULT"

    # Extract first movie ID
    if command -v jq &> /dev/null; then
        MOVIE_ID=$(echo "$SEARCH_RESULT" | jq -r '.data[0].id // empty')
    else
        MOVIE_ID=$(echo "$SEARCH_RESULT" | grep -o '"id":[0-9]*' | head -1 | grep -o '[0-9]*')
    fi

    if [ -z "$MOVIE_ID" ]; then
        echo -e "${RED}No movies found. Please import movies first.${NC}"
        echo -e "${YELLOW}Run: curl -X POST ${API_URL}/movies/import -H 'Content-Type: application/json' -d '{\"source\": \"popular\", \"page\": 1}'${NC}"
        return 1
    fi

    echo -e "\n${YELLOW}Step 2: Getting details for Movie ID: ${MOVIE_ID}...${NC}"
    MOVIE_DETAILS=$(curl -s -X GET "${API_URL}/movies/${MOVIE_ID}" \
        -H "Accept: application/json")

    echo -e "${GREEN}Movie Details:${NC}"
    display_json "$MOVIE_DETAILS"

    echo -e "\n${YELLOW}Step 3: Finding similar movies...${NC}"
    SIMILAR_MOVIES=$(curl -s -X GET "${API_URL}/movies/${MOVIE_ID}/similar?limit=5&threshold=0.6" \
        -H "Accept: application/json")

    echo -e "${GREEN}Similar Movies:${NC}"
    display_json "$SIMILAR_MOVIES"

    echo -e "\n${GREEN}✓ Scenario 1 Complete!${NC}\n"
}

# Test Scenario 2: Plot-based search
test_scenario_2() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}SCENARIO 2: Search by Custom Plot${NC}"
    echo -e "${BLUE}========================================${NC}\n"

    # Test 1: Superhero plot
    echo -e "${YELLOW}Test 2.1: Superhero/Action Plot${NC}"
    PLOT1="A team of heroes with superpowers must work together to save the world from an alien invasion threatening to destroy humanity"

    RESULT1=$(curl -s -X POST "${API_URL}/movies/search-by-plot" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d "{\"plot\": \"$PLOT1\", \"limit\": 5, \"threshold\": 0.6}")

    echo -e "${GREEN}Results:${NC}"
    display_json "$RESULT1"

    echo -e "\n${YELLOW}Test 2.2: Mystery/Thriller Plot${NC}"
    PLOT2="A detective investigates a series of mysterious murders while uncovering dark secrets from the past"

    RESULT2=$(curl -s -X POST "${API_URL}/movies/search-by-plot" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d "{\"plot\": \"$PLOT2\", \"limit\": 5, \"threshold\": 0.6}")

    echo -e "${GREEN}Results:${NC}"
    display_json "$RESULT2"

    echo -e "\n${YELLOW}Test 2.3: Sci-Fi Adventure Plot${NC}"
    PLOT3="A group of astronauts embark on a dangerous mission to explore a distant planet and discover something extraordinary"

    RESULT3=$(curl -s -X POST "${API_URL}/movies/search-by-plot" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d "{\"plot\": \"$PLOT3\", \"limit\": 5, \"threshold\": 0.6}")

    echo -e "${GREEN}Results:${NC}"
    display_json "$RESULT3"

    echo -e "\n${GREEN}✓ Scenario 2 Complete!${NC}\n"
}

# Main menu
show_menu() {
    echo -e "${YELLOW}Select test scenario:${NC}"
    echo -e "  ${GREEN}1${NC} - Test Scenario 1 (Search by Title)"
    echo -e "  ${GREEN}2${NC} - Test Scenario 2 (Search by Custom Plot)"
    echo -e "  ${GREEN}3${NC} - Test Both Scenarios"
    echo -e "  ${GREEN}4${NC} - Setup: Import Movies & Generate Embeddings"
    echo -e "  ${GREEN}q${NC} - Quit"
    echo -e "\nChoice: "
}

# Setup function
setup_data() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}SETUP: Importing Movies${NC}"
    echo -e "${BLUE}========================================${NC}\n"

    echo -e "${YELLOW}Importing popular movies from TMDB...${NC}"
    IMPORT_RESULT=$(curl -s -X POST "${API_URL}/movies/import" \
        -H "Content-Type: application/json" \
        -d '{"source": "popular", "page": 1}')

    echo -e "${GREEN}Import Results:${NC}"
    display_json "$IMPORT_RESULT"

    echo -e "\n${YELLOW}Generating embeddings (this may take a while)...${NC}"
    EMBEDDING_RESULT=$(curl -s -X POST "${API_URL}/movies/batch-generate-embeddings" \
        -H "Content-Type: application/json" \
        -d '{"limit": 50}')

    echo -e "${GREEN}Embedding Results:${NC}"
    display_json "$EMBEDDING_RESULT"

    echo -e "\n${GREEN}✓ Setup Complete!${NC}\n"
}

# Check services first
check_services

# If argument provided, run that test
if [ "$1" == "1" ]; then
    test_scenario_1
    exit 0
elif [ "$1" == "2" ]; then
    test_scenario_2
    exit 0
elif [ "$1" == "both" ] || [ "$1" == "3" ]; then
    test_scenario_1
    echo -e "\n"
    test_scenario_2
    exit 0
elif [ "$1" == "setup" ] || [ "$1" == "4" ]; then
    setup_data
    exit 0
fi

# Interactive mode
while true; do
    show_menu
    read -r choice

    case $choice in
        1)
            test_scenario_1
            ;;
        2)
            test_scenario_2
            ;;
        3)
            test_scenario_1
            echo -e "\n"
            test_scenario_2
            ;;
        4)
            setup_data
            ;;
        q|Q)
            echo -e "${BLUE}Goodbye!${NC}"
            exit 0
            ;;
        *)
            echo -e "${RED}Invalid choice${NC}\n"
            ;;
    esac
done
