<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Acceptance Testing - Feedback Form</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .header p {
            color: #7f8c8d;
            font-size: 16px;
        }

        .feedback-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .rating-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .rating-input {
            display: none;
        }

        .rating-label {
            padding: 8px 16px;
            background: #e9ecef;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .rating-input:checked + .rating-label {
            background: #3498db;
            color: white;
        }

        .rating-label:hover {
            background: #bdc3c7;
        }

        .rating-input:checked + .rating-label:hover {
            background: #2980b9;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
            margin-left: 10px;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .feedback-type-tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 2px solid #e9ecef;
        }

        .tab-button {
            padding: 15px 25px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            color: #7f8c8d;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .tab-button.active {
            color: #3498db;
            border-bottom-color: #3498db;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        .help-text {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }

        .required {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>User Acceptance Testing</h1>
            <p>Help us improve the Graduate Tracking System by providing your feedback</p>
        </div>

        <div id="success-message" class="success-message" style="display: none;">
            Thank you for your feedback! Your input has been recorded.
        </div>

        <div id="error-message" class="error-message" style="display: none;">
            There was an error submitting your feedback. Please try again.
        </div>

        <div class="feedback-form">
            <div class="feedback-type-tabs">
                <button class="tab-button active" onclick="showTab('general')">General Feedback</button>
                <button class="tab-button" onclick="showTab('bug')">Bug Report</button>
                <button class="tab-button" onclick="showTab('usability')">Usability</button>
                <button class="tab-button" onclick="showTab('performance')">Performance</button>
            </div>

            <!-- General Feedback Tab -->
            <div id="general-tab" class="tab-content active">
                <form id="general-feedback-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="test-id">Test Scenario ID</label>
                            <input type="text" id="test-id" name="test_id" placeholder="e.g., SA-001, IA-002">
                            <div class="help-text">Enter the test scenario you're providing feedback for</div>
                        </div>
                        <div class="form-group">
                            <label for="user-role">Your Role</label>
                            <select id="user-role" name="user_role" required>
                                <option value="">Select your role</option>
                                <option value="super_admin">Super Admin</option>
                                <option value="institution_admin">Institution Admin</option>
                                <option value="employer">Employer</option>
                                <option value="graduate">Graduate</option>
                                <option value="tester">Tester</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="feedback">Feedback <span class="required">*</span></label>
                        <textarea id="feedback" name="feedback" required placeholder="Please describe your experience, any issues encountered, or suggestions for improvement..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Submit Feedback</button>
                </form>
            </div>

            <!-- Bug Report Tab -->
            <div id="bug-tab" class="tab-content">
                <form id="bug-report-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="bug-test-id">Test Scenario ID</label>
                            <input type="text" id="bug-test-id" name="test_id" placeholder="e.g., SA-001, IA-002">
                        </div>
                        <div class="form-group">
                            <label for="bug-severity">Severity <span class="required">*</span></label>
                            <select id="bug-severity" name="severity" required>
                                <option value="">Select severity</option>
                                <option value="critical">Critical - System unusable</option>
                                <option value="high">High - Major functionality broken</option>
                                <option value="medium">Medium - Minor functionality issues</option>
                                <option value="low">Low - Cosmetic or enhancement</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="bug-title">Bug Title <span class="required">*</span></label>
                        <input type="text" id="bug-title" name="title" required placeholder="Brief description of the bug">
                    </div>

                    <div class="form-group">
                        <label for="bug-description">Description <span class="required">*</span></label>
                        <textarea id="bug-description" name="description" required placeholder="Detailed description of the bug"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="steps-to-reproduce">Steps to Reproduce <span class="required">*</span></label>
                        <textarea id="steps-to-reproduce" name="steps_to_reproduce" required placeholder="1. Go to...&#10;2. Click on...&#10;3. Enter...&#10;4. Observe..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="expected-result">Expected Result</label>
                            <textarea id="expected-result" name="expected_result" placeholder="What should happen?"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="actual-result">Actual Result</label>
                            <textarea id="actual-result" name="actual_result" placeholder="What actually happened?"></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="browser">Browser</label>
                            <input type="text" id="browser" name="browser" placeholder="e.g., Chrome 91.0, Firefox 89.0">
                        </div>
                        <div class="form-group">
                            <label for="os">Operating System</label>
                            <input type="text" id="os" name="os" placeholder="e.g., Windows 10, macOS 11.4">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-danger">Submit Bug Report</button>
                </form>
            </div>

            <!-- Usability Tab -->
            <div id="usability-tab" class="tab-content">
                <form id="usability-form">
                    <div class="form-group">
                        <label for="usability-test-id">Test Scenario ID</label>
                        <input type="text" id="usability-test-id" name="test_id" placeholder="e.g., SA-001, IA-002">
                    </div>

                    <div class="form-group">
                        <label>Ease of Use (1 = Very Difficult, 5 = Very Easy)</label>
                        <div class="rating-group">
                            <input type="radio" id="ease-1" name="ease_of_use_rating" value="1" class="rating-input">
                            <label for="ease-1" class="rating-label">1</label>
                            <input type="radio" id="ease-2" name="ease_of_use_rating" value="2" class="rating-input">
                            <label for="ease-2" class="rating-label">2</label>
                            <input type="radio" id="ease-3" name="ease_of_use_rating" value="3" class="rating-input">
                            <label for="ease-3" class="rating-label">3</label>
                            <input type="radio" id="ease-4" name="ease_of_use_rating" value="4" class="rating-input">
                            <label for="ease-4" class="rating-label">4</label>
                            <input type="radio" id="ease-5" name="ease_of_use_rating" value="5" class="rating-input">
                            <label for="ease-5" class="rating-label">5</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Navigation (1 = Very Confusing, 5 = Very Clear)</label>
                        <div class="rating-group">
                            <input type="radio" id="nav-1" name="navigation_rating" value="1" class="rating-input">
                            <label for="nav-1" class="rating-label">1</label>
                            <input type="radio" id="nav-2" name="navigation_rating" value="2" class="rating-input">
                            <label for="nav-2" class="rating-label">2</label>
                            <input type="radio" id="nav-3" name="navigation_rating" value="3" class="rating-input">
                            <label for="nav-3" class="rating-label">3</label>
                            <input type="radio" id="nav-4" name="navigation_rating" value="4" class="rating-input">
                            <label for="nav-4" class="rating-label">4</label>
                            <input type="radio" id="nav-5" name="navigation_rating" value="5" class="rating-input">
                            <label for="nav-5" class="rating-label">5</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Visual Design (1 = Very Poor, 5 = Excellent)</label>
                        <div class="rating-group">
                            <input type="radio" id="design-1" name="visual_design_rating" value="1" class="rating-input">
                            <label for="design-1" class="rating-label">1</label>
                            <input type="radio" id="design-2" name="visual_design_rating" value="2" class="rating-input">
                            <label for="design-2" class="rating-label">2</label>
                            <input type="radio" id="design-3" name="visual_design_rating" value="3" class="rating-input">
                            <label for="design-3" class="rating-label">3</label>
                            <input type="radio" id="design-4" name="visual_design_rating" value="4" class="rating-input">
                            <label for="design-4" class="rating-label">4</label>
                            <input type="radio" id="design-5" name="visual_design_rating" value="5" class="rating-input">
                            <label for="design-5" class="rating-label">5</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Overall Satisfaction (1 = Very Unsatisfied, 5 = Very Satisfied)</label>
                        <div class="rating-group">
                            <input type="radio" id="satisfaction-1" name="overall_satisfaction" value="1" class="rating-input">
                            <label for="satisfaction-1" class="rating-label">1</label>
                            <input type="radio" id="satisfaction-2" name="overall_satisfaction" value="2" class="rating-input">
                            <label for="satisfaction-2" class="rating-label">2</label>
                            <input type="radio" id="satisfaction-3" name="overall_satisfaction" value="3" class="rating-input">
                            <label for="satisfaction-3" class="rating-label">3</label>
                            <input type="radio" id="satisfaction-4" name="overall_satisfaction" value="4" class="rating-input">
                            <label for="satisfaction-4" class="rating-label">4</label>
                            <input type="radio" id="satisfaction-5" name="overall_satisfaction" value="5" class="rating-input">
                            <label for="satisfaction-5" class="rating-label">5</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="positive-aspects">What did you like most?</label>
                        <textarea id="positive-aspects" name="positive_aspects" placeholder="Describe the positive aspects of your experience..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="improvement-suggestions">What could be improved?</label>
                        <textarea id="improvement-suggestions" name="improvement_suggestions" placeholder="Suggest improvements or changes..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="most-difficult-task">What was the most difficult task?</label>
                        <textarea id="most-difficult-task" name="most_difficult_task" placeholder="Describe any tasks that were challenging..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Submit Usability Feedback</button>
                </form>
            </div>

            <!-- Performance Tab -->
            <div id="performance-tab" class="tab-content">
                <form id="performance-form">
                    <div class="form-group">
                        <label for="performance-test-id">Test Scenario ID</label>
                        <input type="text" id="performance-test-id" name="test_id" placeholder="e.g., SA-001, IA-002">
                    </div>

                    <div class="form-group">
                        <label>Response Time Rating (1 = Very Slow, 5 = Very Fast)</label>
                        <div class="rating-group">
                            <input type="radio" id="response-1" name="response_time_rating" value="1" class="rating-input">
                            <label for="response-1" class="rating-label">1</label>
                            <input type="radio" id="response-2" name="response_time_rating" value="2" class="rating-input">
                            <label for="response-2" class="rating-label">2</label>
                            <input type="radio" id="response-3" name="response_time_rating" value="3" class="rating-input">
                            <label for="response-3" class="rating-label">3</label>
                            <input type="radio" id="response-4" name="response_time_rating" value="4" class="rating-input">
                            <label for="response-4" class="rating-label">4</label>
                            <input type="radio" id="response-5" name="response_time_rating" value="5" class="rating-input">
                            <label for="response-5" class="rating-label">5</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>System Responsiveness (1 = Very Unresponsive, 5 = Very Responsive)</label>
                        <div class="rating-group">
                            <input type="radio" id="responsive-1" name="system_responsiveness" value="1" class="rating-input">
                            <label for="responsive-1" class="rating-label">1</label>
                            <input type="radio" id="responsive-2" name="system_responsiveness" value="2" class="rating-input">
                            <label for="responsive-2" class="rating-label">2</label>
                            <input type="radio" id="responsive-3" name="system_responsiveness" value="3" class="rating-input">
                            <label for="responsive-3" class="rating-label">3</label>
                            <input type="radio" id="responsive-4" name="system_responsiveness" value="4" class="rating-input">
                            <label for="responsive-4" class="rating-label">4</label>
                            <input type="radio" id="responsive-5" name="system_responsiveness" value="5" class="rating-input">
                            <label for="responsive-5" class="rating-label">5</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="slow-operations">Which operations felt slow?</label>
                        <textarea id="slow-operations" name="slow_operations" placeholder="Describe any operations that took too long..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="timeout-issues">Did you experience any timeouts?</label>
                        <textarea id="timeout-issues" name="timeout_issues" placeholder="Describe any timeout or loading issues..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="browser-performance">Browser Performance Notes</label>
                        <textarea id="browser-performance" name="browser_performance" placeholder="Any browser-specific performance issues..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Submit Performance Feedback</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));

            // Remove active class from all tab buttons
            const tabButtons = document.querySelectorAll('.tab-button');
            tabButtons.forEach(button => button.classList.remove('active'));

            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');

            // Add active class to clicked button
            event.target.classList.add('active');
        }

        // Form submission handlers
        document.getElementById('general-feedback-form').addEventListener('submit', function(e) {
            e.preventDefault();
            submitFeedback('general', this);
        });

        document.getElementById('bug-report-form').addEventListener('submit', function(e) {
            e.preventDefault();
            submitFeedback('bug', this);
        });

        document.getElementById('usability-form').addEventListener('submit', function(e) {
            e.preventDefault();
            submitFeedback('usability', this);
        });

        document.getElementById('performance-form').addEventListener('submit', function(e) {
            e.preventDefault();
            submitFeedback('performance', this);
        });

        function submitFeedback(type, form) {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            // Add type to data
            data.type = type;

            // Get CSRF token
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch('/testing/feedback', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('success', 'Thank you for your feedback! Your input has been recorded.');
                    form.reset();
                } else {
                    showMessage('error', 'There was an error submitting your feedback. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('error', 'There was an error submitting your feedback. Please try again.');
            });
        }

        function showMessage(type, message) {
            const successMessage = document.getElementById('success-message');
            const errorMessage = document.getElementById('error-message');

            // Hide both messages first
            successMessage.style.display = 'none';
            errorMessage.style.display = 'none';

            if (type === 'success') {
                successMessage.textContent = message;
                successMessage.style.display = 'block';
            } else {
                errorMessage.textContent = message;
                errorMessage.style.display = 'block';
            }

            // Scroll to top to show message
            window.scrollTo(0, 0);

            // Hide message after 5 seconds
            setTimeout(() => {
                successMessage.style.display = 'none';
                errorMessage.style.display = 'none';
            }, 5000);
        }
    </script>
</body>
</html>