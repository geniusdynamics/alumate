<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Preview - {{ $device ?? 'Desktop' }}</title>
    
    <!-- Tailwind CSS -->
    @vite(['resources/css/app.css'])
    
    <!-- Custom Styles -->
    <style>
        {!! $css !!}
        
        /* Preview-specific styles */
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .preview-container {
            min-height: 100vh;
            background: #f9fafb;
        }
        
        .preview-content {
            background: white;
            min-height: 100vh;
        }
        
        /* Device-specific styles */
        @media (max-width: 768px) {
            .preview-content {
                padding: 1rem;
            }
        }
        
        /* Interaction highlighting for preview mode */
        .preview-highlight {
            outline: 2px solid #3b82f6 !important;
            outline-offset: 2px;
        }
        
        /* Form validation styles for preview */
        .form-error {
            border-color: #ef4444 !important;
        }
        
        .form-success {
            border-color: #10b981 !important;
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <div class="preview-content {{ $deviceClasses ?? '' }}">
            {!! $html !!}
        </div>
    </div>
    
    <!-- Performance Tracking -->
    {!! $performanceScript !!}
    
    <!-- Interaction Tracking (if enabled) -->
    {!! $interactionScript ?? '' !!}
    
    <!-- Preview Enhancement Script -->
    <script>
        (function() {
            // Enhance forms for preview mode
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    
                    // Simulate form submission
                    const formData = new FormData(form);
                    const data = Object.fromEntries(formData.entries());
                    
                    // Show success message
                    const successMsg = document.createElement('div');
                    successMsg.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg z-50';
                    successMsg.textContent = 'Form submitted successfully (Preview Mode)';
                    document.body.appendChild(successMsg);
                    
                    setTimeout(() => {
                        document.body.removeChild(successMsg);
                    }, 3000);
                    
                    // Send form data to parent window
                    if (window.parent !== window) {
                        window.parent.postMessage({
                            type: 'form-submit',
                            data: {
                                formData: data,
                                formAction: form.action || window.location.href,
                                formMethod: form.method || 'GET'
                            }
                        }, '*');
                    }
                });
            });
            
            // Enhance links for preview mode
            const links = document.querySelectorAll('a[href]');
            links.forEach(link => {
                link.addEventListener('click', (e) => {
                    const href = link.getAttribute('href');
                    
                    // Prevent navigation for internal links in preview
                    if (href && (href.startsWith('#') || href.startsWith('/'))) {
                        e.preventDefault();
                        
                        // Show navigation message
                        const navMsg = document.createElement('div');
                        navMsg.className = 'fixed top-4 right-4 bg-blue-500 text-white px-4 py-2 rounded shadow-lg z-50';
                        navMsg.textContent = `Would navigate to: ${href} (Preview Mode)`;
                        document.body.appendChild(navMsg);
                        
                        setTimeout(() => {
                            document.body.removeChild(navMsg);
                        }, 3000);
                        
                        // Send navigation data to parent window
                        if (window.parent !== window) {
                            window.parent.postMessage({
                                type: 'navigation',
                                data: {
                                    href: href,
                                    text: link.textContent.trim()
                                }
                            }, '*');
                        }
                    }
                });
            });
            
            // Add hover effects for interactive elements
            const interactiveElements = document.querySelectorAll('button, a, input, select, textarea');
            interactiveElements.forEach(element => {
                element.addEventListener('mouseenter', () => {
                    element.classList.add('preview-highlight');
                });
                
                element.addEventListener('mouseleave', () => {
                    element.classList.remove('preview-highlight');
                });
            });
            
            // Simulate responsive behavior
            const handleResize = () => {
                const width = window.innerWidth;
                let deviceType = 'desktop';
                
                if (width < 768) {
                    deviceType = 'mobile';
                } else if (width < 1024) {
                    deviceType = 'tablet';
                }
                
                // Send device change to parent window
                if (window.parent !== window) {
                    window.parent.postMessage({
                        type: 'device-change',
                        data: {
                            device: deviceType,
                            width: width,
                            height: window.innerHeight
                        }
                    }, '*');
                }
            };
            
            window.addEventListener('resize', handleResize);
            handleResize(); // Initial call
            
            // Listen for messages from parent window
            window.addEventListener('message', (event) => {
                if (event.data.type === 'update-content') {
                    // Update content dynamically
                    const contentContainer = document.querySelector('.preview-content');
                    if (contentContainer && event.data.html) {
                        contentContainer.innerHTML = event.data.html;
                    }
                }
                
                if (event.data.type === 'update-styles') {
                    // Update styles dynamically
                    let styleElement = document.getElementById('dynamic-styles');
                    if (!styleElement) {
                        styleElement = document.createElement('style');
                        styleElement.id = 'dynamic-styles';
                        document.head.appendChild(styleElement);
                    }
                    styleElement.textContent = event.data.css;
                }
            });
            
            // Notify parent that preview is ready
            if (window.parent !== window) {
                window.parent.postMessage({
                    type: 'preview-ready',
                    data: {
                        device: '{{ $device ?? "desktop" }}',
                        timestamp: Date.now()
                    }
                }, '*');
            }
        })();
    </script>
</body>
</html>