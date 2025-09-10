<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HookBytes - Enterprise Webhook Gateway</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    
    <!-- Styles -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Inter', sans-serif;
                line-height: 1.6;
                color: #1a1a1a;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
            }
            
            .container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 0 20px;
            }
            
            .header {
                padding: 20px 0;
                background: rgba(255, 255, 255, 0.1);
                backdrop-filter: blur(10px);
                border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            }
            
            .nav {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .logo {
                font-size: 24px;
                font-weight: 700;
                color: white;
                text-decoration: none;
            }
            
            .nav-links {
                display: flex;
                gap: 30px;
                align-items: center;
            }
            
            .nav-link {
                color: white;
                text-decoration: none;
                font-weight: 500;
                transition: opacity 0.3s;
            }
            
            .nav-link:hover {
                opacity: 0.8;
            }
            
            .btn {
                padding: 12px 24px;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 600;
                transition: all 0.3s;
                border: none;
                cursor: pointer;
                display: inline-block;
            }
            
            .btn-primary {
                background: #4f46e5;
                color: white;
            }
            
            .btn-primary:hover {
                background: #4338ca;
                transform: translateY(-2px);
            }
            
            .btn-secondary {
                background: rgba(255, 255, 255, 0.2);
                color: white;
                border: 1px solid rgba(255, 255, 255, 0.3);
            }
            
            .btn-secondary:hover {
                background: rgba(255, 255, 255, 0.3);
            }
            
            .hero {
                padding: 100px 0;
                text-align: center;
                color: white;
            }
            
            .hero h1 {
                font-size: 3.5rem;
                font-weight: 700;
                margin-bottom: 20px;
                line-height: 1.2;
            }
            
            .hero p {
                font-size: 1.25rem;
                margin-bottom: 40px;
                opacity: 0.9;
                max-width: 600px;
                margin-left: auto;
                margin-right: auto;
            }
            
            .hero-buttons {
                display: flex;
                gap: 20px;
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .features {
                padding: 100px 0;
                background: white;
            }
            
            .features h2 {
                text-align: center;
                font-size: 2.5rem;
                font-weight: 700;
                margin-bottom: 60px;
                color: #1a1a1a;
            }
            
            .features-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 40px;
            }
            
            .feature-card {
                padding: 40px;
                border-radius: 12px;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                transition: transform 0.3s, box-shadow 0.3s;
            }
            
            .feature-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 20px;
                color: white;
                font-size: 24px;
            }
            
            .feature-card h3 {
                font-size: 1.5rem;
                font-weight: 600;
                margin-bottom: 15px;
                color: #1a1a1a;
            }
            
            .feature-card p {
                color: #64748b;
                line-height: 1.6;
            }
            
            .stats {
                padding: 80px 0;
                background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
                color: white;
            }
            
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 40px;
                text-align: center;
            }
            
            .stat-number {
                font-size: 3rem;
                font-weight: 700;
                margin-bottom: 10px;
                color: #60a5fa;
            }
            
            .stat-label {
                font-size: 1.1rem;
                opacity: 0.9;
            }
            
            .footer {
                padding: 60px 0;
                background: #0f172a;
                color: white;
                text-align: center;
            }
            
            .footer-content {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 40px;
                margin-bottom: 40px;
                text-align: left;
            }
            
            .footer-section h4 {
                font-size: 1.2rem;
                font-weight: 600;
                margin-bottom: 20px;
            }
            
            .footer-section a {
                color: #94a3b8;
                text-decoration: none;
                display: block;
                margin-bottom: 10px;
                transition: color 0.3s;
            }
            
            .footer-section a:hover {
                color: white;
            }
            
            .footer-bottom {
                border-top: 1px solid #334155;
                padding-top: 30px;
                text-align: center;
                color: #94a3b8;
            }
            
            @media (max-width: 768px) {
                .hero h1 {
                    font-size: 2.5rem;
                }
                
                .hero-buttons {
                    flex-direction: column;
                    align-items: center;
                }
                
                .nav-links {
                    display: none;
                }
            }
        </style>
    @endif
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="nav">
                <a href="/" class="logo">🪝 HookBytes</a>
                <div class="nav-links">
                    <a href="#features" class="nav-link">Features</a>
                    <a href="https://hookbytes.io" class="nav-link">Docs</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-primary">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary">See Demo</a>
                    @endauth
                </div>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Enterprise Webhook Gateway</h1>
            <p>Reliable, scalable, and secure webhook management for modern applications. Process millions of webhooks with enterprise-grade monitoring and analytics.</p>
            <div class="hero-buttons">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">See Demo</a>
                @endauth
                <a href="https://github.com/noibilism/hookbytes" class="btn btn-secondary">View on GitHub</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <h2>Why Choose HookBytes?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3>High Performance</h3>
                    <p>Process millions of webhooks per hour with our optimized infrastructure. Built for scale with automatic load balancing and intelligent routing.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔒</div>
                    <h3>Enterprise Security</h3>
                    <p>Advanced authentication methods including HMAC signatures, API keys, and custom headers. SOC 2 compliant with end-to-end encryption.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Real-time Analytics</h3>
                    <p>Comprehensive monitoring and analytics dashboard. Track delivery rates, response times, and error patterns in real-time.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔄</div>
                    <h3>Smart Retry Logic</h3>
                    <p>Intelligent retry mechanisms with exponential backoff. Ensure webhook delivery even when endpoints are temporarily unavailable.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🎯</div>
                    <h3>Event Filtering</h3>
                    <p>Advanced filtering and routing capabilities. Send specific events to different endpoints based on custom rules and conditions.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🛠️</div>
                    <h3>Developer Friendly</h3>
                    <p>RESTful API, comprehensive documentation, and SDKs for popular programming languages. Get started in minutes, not hours.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat">
                    <div class="stat-number">99.9%</div>
                    <div class="stat-label">Uptime SLA</div>
                </div>
                <div class="stat">
                    <div class="stat-number">10M+</div>
                    <div class="stat-label">Webhooks Processed</div>
                </div>
                <div class="stat">
                    <div class="stat-number">500+</div>
                    <div class="stat-label">Enterprise Customers</div>
                </div>
                <div class="stat">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Expert Support</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Product</h4>
                    <a href="#features">Features</a>
                    <a href="https://hookbytes.io" target="_blank">Documentation</a>
                    <a href="https://github.com/noibilism/hookbytes" target="_blank">API Reference</a>
                </div>
                <div class="footer-section">
                    <h4>Company</h4>
                    <a href="/about">About</a>
                    <a href="/blog">Blog</a>
                    <a href="/careers">Careers</a>
                    <a href="/contact">Contact</a>
                </div>
                <div class="footer-section">
                    <h4>Resources</h4>
                    <a href="https://github.com/noibilism/hookbytes">GitHub</a>
                    <a href="/community">Community</a>
                    <a href="/support">Support</a>
                    <a href="/security">Security</a>
                </div>
                <div class="footer-section">
                    <h4>Legal</h4>
                    <a href="/privacy">Privacy Policy</a>
                    <a href="/terms">Terms of Service</a>
                    <a href="/cookies">Cookie Policy</a>
                    <a href="/compliance">Compliance</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} HookBytes. All rights reserved. Built with ❤️ for developers.</p>
            </div>
        </div>
    </footer>
</body>
</html>
