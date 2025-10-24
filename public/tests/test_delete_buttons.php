<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Function Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3><i class="fas fa-vial me-2"></i>Delete Function Test</h3>
                <p class="mb-0">This page tests if delete buttons work with special characters</p>
            </div>
            <div class="card-body">
                <h5>Test Cases:</h5>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Instructions:</strong> Click each delete button below. A confirmation dialog should appear.
                    If you see a JavaScript error in the console (F12), the fix didn't work.
                </div>

                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Test Case</th>
                            <th>Name/Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Test cases with special characters
                        $testCases = [
                            ['type' => 'Customer', 'id' => 1, 'name' => "John O'Brien"],
                            ['type' => 'Customer', 'id' => 2, 'name' => 'Jane "The Boss" Smith'],
                            ['type' => 'Customer', 'id' => 3, 'name' => "Mike D'Angelo"],
                            ['type' => 'Vehicle', 'id' => 101, 'name' => "2020 Ford F-150"],
                            ['type' => 'Vehicle', 'id' => 102, 'name' => '2018 Chevy "Silverado"'],
                            ['type' => 'Vehicle', 'id' => 103, 'name' => "2021 Ram 'Big Horn'"],
                            ['type' => 'Mechanic', 'id' => 201, 'name' => "Robert 'Bob' Johnson"],
                            ['type' => 'Mechanic', 'id' => 202, 'name' => 'Sarah & Associates'],
                            ['type' => 'Mechanic', 'id' => 203, 'name' => 'José García'],
                        ];

                        foreach ($testCases as $test):
                        ?>
                        <tr>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($test['type']) ?></span></td>
                            <td><code><?= htmlspecialchars($test['name']) ?></code></td>
                            <td>
                                <button class="btn btn-sm btn-danger" 
                                        onclick='testDelete(<?= (int)$test['id'] ?>, <?= htmlspecialchars(json_encode($test['name']), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($test['type']), ENT_QUOTES) ?>)'>
                                    <i class="fas fa-trash-alt me-1"></i>Delete
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="mt-4">
                    <h5>Console Log:</h5>
                    <div id="logOutput" class="border rounded p-3 bg-white" style="max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 0.9em;">
                        <div class="text-muted">Click delete buttons to see results...</div>
                    </div>
                </div>

                <div class="mt-4">
                    <h5>Test Results:</h5>
                    <div id="results">
                        <div class="alert alert-warning">
                            <i class="fas fa-clock me-2"></i>No tests run yet
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    let testResults = {
        passed: 0,
        failed: 0,
        total: 0
    };

    function log(message, type = 'info') {
        const logDiv = document.getElementById('logOutput');
        const timestamp = new Date().toLocaleTimeString();
        const colors = {
            info: 'text-primary',
            success: 'text-success',
            error: 'text-danger',
            warning: 'text-warning'
        };
        
        const entry = document.createElement('div');
        entry.className = colors[type] || 'text-dark';
        entry.innerHTML = `<strong>[${timestamp}]</strong> ${message}`;
        logDiv.appendChild(entry);
        logDiv.scrollTop = logDiv.scrollHeight;
    }

    function updateResults() {
        const resultsDiv = document.getElementById('results');
        const passed = testResults.passed;
        const failed = testResults.failed;
        const total = testResults.total;
        
        let alertClass = 'alert-warning';
        let icon = 'fa-clock';
        
        if (total > 0) {
            if (failed === 0) {
                alertClass = 'alert-success';
                icon = 'fa-check-circle';
            } else {
                alertClass = 'alert-danger';
                icon = 'fa-exclamation-circle';
            }
        }
        
        resultsDiv.innerHTML = `
            <div class="alert ${alertClass}">
                <i class="fas ${icon} me-2"></i>
                <strong>Tests Run:</strong> ${total} | 
                <strong>Passed:</strong> ${passed} | 
                <strong>Failed:</strong> ${failed}
            </div>
        `;
    }

    function testDelete(id, name, type) {
        testResults.total++;
        
        try {
            log(`Testing delete for ${type} ID ${id}: "${name}"`, 'info');
            
            // Check if name was passed correctly
            if (typeof name !== 'string') {
                throw new Error('Name parameter is not a string!');
            }
            
            if (typeof id !== 'number') {
                throw new Error('ID parameter is not a number!');
            }
            
            log(`✓ Parameters received correctly - ID: ${id}, Name: "${name}"`, 'success');
            
            // Show SweetAlert2 confirmation
            Swal.fire({
                title: 'Test Successful!',
                html: `
                    <p><strong>Type:</strong> ${type}</p>
                    <p><strong>ID:</strong> ${id}</p>
                    <p><strong>Name:</strong> "${name}"</p>
                    <p class="text-success mt-3">✓ Delete button works correctly!</p>
                `,
                icon: 'success',
                confirmButtonText: 'OK',
                confirmButtonColor: '#28a745'
            });
            
            testResults.passed++;
            log(`✓ Test PASSED for ${type} "${name}"`, 'success');
            
        } catch (error) {
            testResults.failed++;
            log(`✗ Test FAILED: ${error.message}`, 'error');
            console.error('Delete test error:', error);
            
            Swal.fire({
                title: 'Test Failed!',
                text: error.message,
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
        }
        
        updateResults();
    }

    // Log page load
    document.addEventListener('DOMContentLoaded', function() {
        log('Test page loaded successfully', 'success');
        log('JavaScript is working correctly', 'success');
        log('SweetAlert2 library loaded: ' + (typeof Swal !== 'undefined' ? 'YES' : 'NO'), 
            typeof Swal !== 'undefined' ? 'success' : 'error');
    });

    // Catch any JavaScript errors
    window.addEventListener('error', function(e) {
        log(`JavaScript Error: ${e.message}`, 'error');
        testResults.failed++;
        updateResults();
    });
    </script>
</body>
</html>
