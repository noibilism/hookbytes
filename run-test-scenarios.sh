#!/bin/bash

# Webhook Gateway Test Scenarios Runner
# This script runs comprehensive test scenarios for interface and feature testing

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to run a specific test file
run_test_file() {
    local test_file=$1
    local description=$2
    
    print_status "Running $description..."
    
    if php artisan test "$test_file" --verbose; then
        print_success "$description completed successfully"
        return 0
    else
        print_error "$description failed"
        return 1
    fi
}

# Function to run tests with coverage
run_with_coverage() {
    local test_file=$1
    local description=$2
    
    print_status "Running $description with coverage..."
    
    if php artisan test "$test_file" --coverage --min=80; then
        print_success "$description completed with adequate coverage"
        return 0
    else
        print_warning "$description completed but coverage may be insufficient"
        return 1
    fi
}

# Main execution
main() {
    echo "======================================"
    echo "  Webhook Gateway Test Scenarios"
    echo "======================================"
    echo ""
    
    # Check if we're in the right directory
    if [ ! -f "artisan" ]; then
        print_error "Please run this script from the Laravel project root directory"
        exit 1
    fi
    
    # Check if test files exist
    test_files=(
        "tests/Feature/IntegrationWorkflowTest.php"
        "tests/Feature/SecurityTest.php"
        "tests/Feature/ErrorHandlingTest.php"
        "tests/Feature/ApiEndpointTest.php"
        "tests/Feature/PerformanceTest.php"
        "tests/Feature/UiInteractionTest.php"
    )
    
    missing_files=()
    for file in "${test_files[@]}"; do
        if [ ! -f "$file" ]; then
            missing_files+=("$file")
        fi
    done
    
    if [ ${#missing_files[@]} -gt 0 ]; then
        print_error "Missing test files:"
        for file in "${missing_files[@]}"; do
            echo "  - $file"
        done
        exit 1
    fi
    
    # Parse command line arguments
    RUN_ALL=true
    RUN_COVERAGE=false
    SPECIFIC_TEST=""
    VERBOSE=false
    
    while [[ $# -gt 0 ]]; do
        case $1 in
            --coverage)
                RUN_COVERAGE=true
                shift
                ;;
            --test)
                SPECIFIC_TEST="$2"
                RUN_ALL=false
                shift 2
                ;;
            --verbose)
                VERBOSE=true
                shift
                ;;
            --help)
                echo "Usage: $0 [OPTIONS]"
                echo ""
                echo "Options:"
                echo "  --coverage          Run tests with coverage analysis"
                echo "  --test <name>       Run specific test scenario"
                echo "  --verbose           Enable verbose output"
                echo "  --help              Show this help message"
                echo ""
                echo "Available test scenarios:"
                echo "  integration         Integration workflow tests"
                echo "  security            Security and authentication tests"
                echo "  error-handling      Error handling and edge cases"
                echo "  api-endpoints       API endpoint tests"
                echo "  performance         Performance and load tests"
                echo "  ui-interaction      UI interaction tests"
                echo "  existing            Run existing test suite"
                echo "  all                 Run all test scenarios (default)"
                exit 0
                ;;
            *)
                print_error "Unknown option: $1"
                echo "Use --help for usage information"
                exit 1
                ;;
        esac
    done
    
    # Set up test environment
    print_status "Setting up test environment..."
    
    # Copy environment file for testing
    if [ ! -f ".env.testing" ]; then
        if [ -f ".env.example" ]; then
            cp .env.example .env.testing
            print_status "Created .env.testing from .env.example"
        else
            print_warning "No .env.testing file found. Tests may fail."
        fi
    fi
    
    # Clear and prepare test database
    print_status "Preparing test database..."
    php artisan config:clear
    php artisan cache:clear
    
    # Track test results
    test_results=()
    failed_tests=()
    
    # Function to execute specific test scenario
    run_scenario() {
        local scenario=$1
        local file=$2
        local description=$3
        
        echo ""
        echo "======================================"
        echo "  $description"
        echo "======================================"
        
        if [ "$RUN_COVERAGE" = true ]; then
            if run_with_coverage "$file" "$description"; then
                test_results+=("$scenario:PASSED")
            else
                test_results+=("$scenario:FAILED")
                failed_tests+=("$scenario")
            fi
        else
            if run_test_file "$file" "$description"; then
                test_results+=("$scenario:PASSED")
            else
                test_results+=("$scenario:FAILED")
                failed_tests+=("$scenario")
            fi
        fi
    }
    
    # Run specific test or all tests
    if [ "$RUN_ALL" = true ] || [ "$SPECIFIC_TEST" = "all" ]; then
        # Run all test scenarios
        run_scenario "integration" "tests/Feature/IntegrationWorkflowTest.php" "Integration Workflow Tests"
        run_scenario "security" "tests/Feature/SecurityTest.php" "Security Tests"
        run_scenario "error-handling" "tests/Feature/ErrorHandlingTest.php" "Error Handling Tests"
        run_scenario "api-endpoints" "tests/Feature/ApiEndpointTest.php" "API Endpoint Tests"
        run_scenario "performance" "tests/Feature/PerformanceTest.php" "Performance Tests"
        run_scenario "ui-interaction" "tests/Feature/UiInteractionTest.php" "UI Interaction Tests"
        
        # Also run existing test suite
        echo ""
        echo "======================================"
        echo "  Existing Test Suite"
        echo "======================================"
        
        if php artisan test --exclude-group=slow; then
            test_results+=("existing:PASSED")
            print_success "Existing test suite completed successfully"
        else
            test_results+=("existing:FAILED")
            failed_tests+=("existing")
            print_error "Existing test suite failed"
        fi
        
    else
        # Run specific test scenario
        case $SPECIFIC_TEST in
            integration)
                run_scenario "integration" "tests/Feature/IntegrationWorkflowTest.php" "Integration Workflow Tests"
                ;;
            security)
                run_scenario "security" "tests/Feature/SecurityTest.php" "Security Tests"
                ;;
            error-handling)
                run_scenario "error-handling" "tests/Feature/ErrorHandlingTest.php" "Error Handling Tests"
                ;;
            api-endpoints)
                run_scenario "api-endpoints" "tests/Feature/ApiEndpointTest.php" "API Endpoint Tests"
                ;;
            performance)
                run_scenario "performance" "tests/Feature/PerformanceTest.php" "Performance Tests"
                ;;
            ui-interaction)
                run_scenario "ui-interaction" "tests/Feature/UiInteractionTest.php" "UI Interaction Tests"
                ;;
            existing)
                echo ""
                echo "======================================"
                echo "  Existing Test Suite"
                echo "======================================"
                
                if php artisan test; then
                    test_results+=("existing:PASSED")
                    print_success "Existing test suite completed successfully"
                else
                    test_results+=("existing:FAILED")
                    failed_tests+=("existing")
                    print_error "Existing test suite failed"
                fi
                ;;
            *)
                print_error "Unknown test scenario: $SPECIFIC_TEST"
                echo "Use --help to see available scenarios"
                exit 1
                ;;
        esac
    fi
    
    # Print summary
    echo ""
    echo "======================================"
    echo "  Test Results Summary"
    echo "======================================"
    
    total_tests=${#test_results[@]}
    passed_tests=0
    
    for result in "${test_results[@]}"; do
        scenario=$(echo "$result" | cut -d: -f1)
        status=$(echo "$result" | cut -d: -f2)
        if [ "$status" = "PASSED" ]; then
            print_success "$scenario: $status"
            ((passed_tests++))
        else
            print_error "$scenario: $status"
        fi
    done
    
    echo ""
    echo "Total: $total_tests scenarios"
    echo "Passed: $passed_tests"
    echo "Failed: $((total_tests - passed_tests))"
    
    if [ ${#failed_tests[@]} -gt 0 ]; then
        echo ""
        print_error "Failed test scenarios:"
        for test in "${failed_tests[@]}"; do
            echo "  - $test"
        done
        
        echo ""
        print_status "To run a specific failed test:"
        echo "  $0 --test <scenario-name>"
        
        exit 1
    else
        echo ""
        print_success "All test scenarios passed successfully! 🎉"
        
        if [ "$RUN_COVERAGE" = true ]; then
            echo ""
            print_status "Coverage reports have been generated."
        fi
        
        exit 0
    fi
}

# Run main function
main "$@"