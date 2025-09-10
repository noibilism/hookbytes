<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HookBytes - Never miss an event.</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
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
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                line-height: 1.6;
                color: #1a1a1a;
                background: #ffffff;
                font-size: 16px;
            }
            
            .container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 0 24px;
            }
            
            /* Header */
            .header {
                position: sticky;
                top: 0;
                z-index: 100;
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                border-bottom: 1px solid #e5e7eb;
                padding: 16px 0;
            }
            
            .nav {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .logo {
                font-size: 24px;
                font-weight: 700;
                color: #1a1a1a;
                text-decoration: none;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .nav-links {
                display: flex;
                gap: 32px;
                align-items: center;
            }
            
            .nav-link {
                color: #6b7280;
                text-decoration: none;
                font-weight: 500;
                font-size: 15px;
                transition: color 0.2s;
            }
            
            .nav-link:hover {
                color: #1a1a1a;
            }
            
            .btn {
                padding: 12px 20px;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 600;
                font-size: 14px;
                transition: all 0.2s;
                border: none;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }
            
            .btn-primary {
                background: #2563eb;
                color: white;
            }
            
            .btn-primary:hover {
                background: #1d4ed8;
            }
            
            .btn-secondary {
                background: transparent;
                color: #6b7280;
                border: 1px solid #d1d5db;
            }
            
            .btn-secondary:hover {
                background: #f9fafb;
                color: #1a1a1a;
            }
            
            /* Hero Section */
            .hero {
                padding: 120px 0 80px;
                text-align: center;
                background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            }
            
            .hero-badge {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                background: #eff6ff;
                color: #2563eb;
                padding: 8px 16px;
                border-radius: 24px;
                font-size: 14px;
                font-weight: 500;
                margin-bottom: 24px;
                border: 1px solid #dbeafe;
            }
            
            .hero h1 {
                font-size: 64px;
                font-weight: 800;
                line-height: 1.1;
                margin-bottom: 24px;
                color: #1a1a1a;
                letter-spacing: -0.02em;
            }
            
            .hero-subtitle {
                font-size: 24px;
                color: #6b7280;
                margin-bottom: 40px;
                max-width: 720px;
                margin-left: auto;
                margin-right: auto;
                line-height: 1.4;
            }
            
            .hero-buttons {
                display: flex;
                gap: 16px;
                justify-content: center;
                flex-wrap: wrap;
                margin-bottom: 80px;
            }
            
            .btn-large {
                padding: 16px 32px;
                font-size: 16px;
                font-weight: 600;
            }
            
            .trusted-by {
                margin-top: 80px;
                text-align: center;
            }
            
            .trusted-by-text {
                font-size: 14px;
                color: #9ca3af;
                margin-bottom: 32px;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                font-weight: 500;
            }
            
            /* Features Section */
            .features {
                padding: 120px 0;
                background: #ffffff;
            }
            
            .section-header {
                text-align: center;
                margin-bottom: 80px;
            }
            
            .section-badge {
                display: inline-block;
                background: #f0f9ff;
                color: #0369a1;
                padding: 6px 12px;
                border-radius: 16px;
                font-size: 14px;
                font-weight: 500;
                margin-bottom: 16px;
            }
            
            .section-title {
                font-size: 48px;
                font-weight: 700;
                line-height: 1.2;
                margin-bottom: 16px;
                color: #1a1a1a;
                letter-spacing: -0.02em;
            }
            
            .section-description {
                font-size: 20px;
                color: #6b7280;
                max-width: 640px;
                margin: 0 auto;
                line-height: 1.5;
            }
            
            .features-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
                gap: 32px;
            }
            
            .feature-card {
                padding: 32px;
                border-radius: 16px;
                background: #ffffff;
                border: 1px solid #e5e7eb;
                transition: all 0.3s;
            }
            
            .feature-card:hover {
                border-color: #d1d5db;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            }
            
            .feature-icon {
                width: 48px;
                height: 48px;
                background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 20px;
                color: white;
                font-size: 20px;
            }
            
            .feature-card h3 {
                font-size: 20px;
                font-weight: 600;
                margin-bottom: 12px;
                color: #1a1a1a;
            }
            
            .feature-card p {
                color: #6b7280;
                line-height: 1.6;
                font-size: 15px;
            }
            
            /* Stats Section */
            .stats {
                padding: 80px 0;
                background: #f8fafc;
                border-top: 1px solid #e5e7eb;
                border-bottom: 1px solid #e5e7eb;
            }
            
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 48px;
                text-align: center;
            }
            
            .stat-number {
                font-size: 48px;
                font-weight: 800;
                margin-bottom: 8px;
                color: #1a1a1a;
                line-height: 1;
            }
            
            .stat-label {
                font-size: 16px;
                color: #6b7280;
                font-weight: 500;
            }
            
            /* CTA Section */
            .cta {
                padding: 120px 0;
                background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
                color: white;
                text-align: center;
            }
            
            .cta h2 {
                font-size: 48px;
                font-weight: 700;
                margin-bottom: 16px;
                line-height: 1.2;
            }
            
            .cta p {
                font-size: 20px;
                opacity: 0.9;
                margin-bottom: 40px;
                max-width: 600px;
                margin-left: auto;
                margin-right: auto;
            }
            
            .btn-white {
                background: white;
                color: #1a1a1a;
            }
            
            .btn-white:hover {
                background: #f9fafb;
            }
            
            /* Footer */
            .footer {
                padding: 80px 0 40px;
                background: #ffffff;
                border-top: 1px solid #e5e7eb;
            }
            
            .footer-content {
                display: grid;
                grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
                gap: 48px;
                margin-bottom: 48px;
            }
            
            .footer-brand {
                max-width: 320px;
            }
            
            .footer-brand .logo {
                margin-bottom: 16px;
            }
            
            .footer-brand p {
                color: #6b7280;
                line-height: 1.6;
                margin-bottom: 24px;
            }
            
            .footer-section h4 {
                font-size: 16px;
                font-weight: 600;
                margin-bottom: 16px;
                color: #1a1a1a;
            }
            
            .footer-section a {
                color: #6b7280;
                text-decoration: none;
                display: block;
                margin-bottom: 12px;
                font-size: 15px;
                transition: color 0.2s;
            }
            
            .footer-section a:hover {
                color: #1a1a1a;
            }
            
            .footer-bottom {
                border-top: 1px solid #e5e7eb;
                padding-top: 32px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                color: #6b7280;
                font-size: 14px;
            }
            
            .social-links {
                display: flex;
                gap: 16px;
            }
            
            .social-links a {
                color: #6b7280;
                transition: color 0.2s;
            }
            
            .social-links a:hover {
                color: #1a1a1a;
            }
            
            /* Responsive */
            @media (max-width: 768px) {
                .container {
                    padding: 0 16px;
                }
                
                .nav-links {
                    display: none;
                }
                
                .hero {
                    padding: 80px 0 60px;
                }
                
                .hero h1 {
                    font-size: 40px;
                }
                
                .hero-subtitle {
                    font-size: 18px;
                }
                
                .hero-buttons {
                    flex-direction: column;
                    align-items: center;
                }
                
                .section-title {
                    font-size: 32px;
                }
                
                .features-grid {
                    grid-template-columns: 1fr;
                }
                
                .footer-content {
                    grid-template-columns: 1fr;
                    gap: 32px;
                }
                
                .footer-bottom {
                    flex-direction: column;
                    gap: 16px;
                    text-align: center;
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
                <a href="/" class="logo">
                    <span>🪝</span>
                    HookBytes
                </a>
                <div class="nav-links">
                    <a href="#features" class="nav-link">Features</a>
                    <a href="https://github.com/noibilism/hookbytes" class="nav-link">Docs</a>
                    <a href="#pricing" class="nav-link">Pricing</a>
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
            <div class="hero-badge">
                <span>🚀</span>
                Meet the Event Gateway
            </div>
            <h1>Never miss an event.</h1>
            <p class="hero-subtitle">
                From webhooks to external event streams, HookBytes ensures every event is received, processed, and monitored reliably at scale, giving you complete visibility and control.
            </p>
            <div class="hero-buttons">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-large">Go to Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary btn-large">See Demo</a>
                @endauth
                <a href="https://github.com/noibilism/hookbytes" class="btn btn-secondary btn-large">Read docs</a>
            </div>
            
            <div class="trusted-by">
                <p class="trusted-by-text">Trusted by great teams around the world</p>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-header">
                <span class="section-badge">Infrastructure</span>
                <h2 class="section-title">Infrastructure for event-driven systems</h2>
                <p class="section-description">
                    HookBytes provides event infrastructure to manage the full lifecycle of external events — from ingestion to delivery to observability.
                </p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📥</div>
                    <h3>Built-in event queueing</h3>
                    <p>Buffer and control event flow with a resilient queue that handles spikes, retries, and destination failures — no infrastructure required.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🔄</div>
                    <h3>Issue management and replay</h3>
                    <p>Automatically surface failed events, inspect metadata, and replay them with confidence, no custom tooling needed.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">💻</div>
                    <h3>Develop locally with real traffic</h3>
                    <p>Use the HookBytes CLI to route development events to localhost — with your whole team.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🎯</div>
                    <h3>Flexible routing and transformation</h3>
                    <p>Filter, transform, and route events to one or many destinations based on payload content or metadata.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Monitor and debug event flows</h3>
                    <p>Gain full visibility into every event flow with real-time tracing, issue replay, and complete delivery metrics.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3>Designed to scale</h3>
                    <p>Ensure smooth delivery during peak usage with built-in resilience controls that absorb traffic spikes.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat">
                    <div class="stat-number">99.999%</div>
                    <div class="stat-label">Uptime</div>
                </div>
                <div class="stat">
                    <div class="stat-number">&lt; 3s</div>
                    <div class="stat-label">Worldwide P99</div>
                </div>
                <div class="stat">
                    <div class="stat-number">5,000+</div>
                    <div class="stat-label">Events/second</div>
                </div>
                <div class="stat">
                    <div class="stat-number">SOC2</div>
                    <div class="stat-label">Compliant</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <h2>Ready to get started?</h2>
            <p>Join thousands of developers who trust HookBytes with their critical webhook infrastructure.</p>
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-white btn-large">Go to Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-white btn-large">See Demo</a>
            @endauth
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <a href="/" class="logo">
                        <span>🪝</span>
                        HookBytes
                    </a>
                    <p>Enterprise webhook gateway that ensures every event is received, processed, and monitored reliably at scale.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Product</h4>
                    <a href="#features">Features</a>
                    <a href="#pricing">Pricing</a>
                    <a href="/changelog">Changelog</a>
                    <a href="/roadmap">Roadmap</a>
                </div>
                
                <div class="footer-section">
                    <h4>Developers</h4>
                    <a href="https://github.com/noibilism/hookbytes" target="_blank">Documentation</a>
                    <a href="https://github.com/noibilism/hookbytes" target="_blank">API Reference</a>
                    <a href="/guides">Guides</a>
                    <a href="/examples">Examples</a>
                </div>
                
                <div class="footer-section">
                    <h4>Company</h4>
                    <a href="/about">About</a>
                    <a href="/blog">Blog</a>
                    <a href="/careers">Careers</a>
                    <a href="/contact">Contact</a>
                </div>
                
                <div class="footer-section">
                    <h4>Legal</h4>
                    <a href="/privacy">Privacy</a>
                    <a href="/terms">Terms</a>
                    <a href="/security">Security</a>
                    <a href="/compliance">Compliance</a>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} HookBytes. All rights reserved.</p>
                <div class="social-links">
                    <a href="https://github.com/noibilism/hookbytes" target="_blank">GitHub</a>
                    <a href="https://twitter.com/hookbytes" target="_blank">Twitter</a>
                    <a href="/discord" target="_blank">Discord</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
