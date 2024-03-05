#!/bin/bash


PROJECT_ID=$DEV_TEST_PROJECT_ID;

function test_gcloud_command() {
    echo "Testing gcloud command..."
    gcloud projects list --project $PROJECT_ID
}

# Perform GCP API request using curl
function test_curl_request() {
    echo "Testing curl request..."
    curl -X GET -H "Authorization: Bearer $(gcloud auth print-access-token)" "https://dev-test-project27547.googleapis.com/v1/resource"
}

# Perform GCP API request using a client library
function test_client_library() {
    echo "Testing client library request..."
    php -r '
    use Google\Cloud\Storage\StorageClient;

    function test_storage() {
        // Create a client using the service account credentials
        $storage = new StorageClient();

        // List buckets in the project
        $buckets = $storage->buckets();
        echo "Buckets in project:\n";
        foreach ($buckets as $bucket) {
            echo $bucket->name() . "\n";
        }
    }

    test_storage();
    '
}


function run_unit_tests() {
    echo "Running unit tests..."
    phpunit tests/Unit
    echo "Unit tests completed."
}

# Perform integration tests
function run_integration_tests() {
    echo "Running integration tests..."
    phpunit tests/Integration
    echo "Integration tests completed."
}

# Perform functional tests
function run_functional_tests() {
    echo "Running functional tests..."
    phpunit tests/Functional
    echo "Functional tests completed."
}

# Perform load testing
function run_load_testing() {
    echo "Running load testing..."
    ab -c 100 -n 1000 http://dev-test-project27547.com/
    echo "Load testing completed."
}


# Perform database connectivity test
function test_database_connectivity() {
    echo "Testing database connectivity..."
    php -r '
    $dbHost = "localhost";
    $dbName = "wb-database";
    $dbUser = "testadminuser";
    $dbPass = getenv("DB_PASSWORD");
    try {
        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
        echo "Database connection successful.\n";
        // dev-test-project27547 query
        $stmt = $pdo->query("SELECT * FROM users");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo "Test query executed successfully.\n";
        } else {
            echo "Test query failed.\n";
        }
    } catch (PDOException $e) {
        echo "Database connection failed: " . $e->getMessage() . "\n";
    }
    '
}


function set_credentials(){
    test_FILE="access"

    test=$(cat "$test_FILE")

    echo "$test" | base64 -d > service-account.json

    TESTAUTH_FILE="service-account.json"

    export GOOGLE_APPLICATION_CREDENTIALS="$TESTAUTH_FILE"
}

# Perform WordPress installation test
function test_wordpress_installation() {
    echo "Testing WordPress installation..."
    curl -I "http://dev-test-project27547.com"
    echo "WordPress installation test completed."
}


# Main run all tests
function run_tests() {
    echo "Running testing script..."

    set_credentials
    test_gcloud_command
    test_curl_request
    test_client_library
    run_unit_tests
    run_integration_tests
    run_functional_tests
    run_load_testing

    echo "All tests completed."
}


# Call the main run tests
run_tests

