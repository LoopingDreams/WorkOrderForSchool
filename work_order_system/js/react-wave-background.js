// React Wave Background Component - Alternative to React Wavify
// This component creates animated wave backgrounds using React and SVG

const ReactWaveBackground = ({
    theme = 'ocean',
    intensity = 'medium',
    speed = 'normal',
    layers = 4,
    showFloatingElements = true,
    className = ''
}) => {
    const { useState, useEffect, useRef } = React;
    
    const [dimensions, setDimensions] = useState({
        width: window.innerWidth,
        height: window.innerHeight
    });
    
    const animationRef = useRef();
    const waveRef = useRef([]);
    
    // Theme configurations
    const themes = {
        ocean: {
            background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            waveColors: ['rgba(255,255,255,0.3)', 'rgba(255,255,255,0.2)', 'rgba(255,255,255,0.15)', 'rgba(255,255,255,0.1)']
        },
        sunset: {
            background: 'linear-gradient(135deg, #ff7e5f 0%, #feb47b 100%)',
            waveColors: ['rgba(255,255,255,0.4)', 'rgba(255,255,255,0.3)', 'rgba(255,255,255,0.2)', 'rgba(255,255,255,0.15)']
        },
        forest: {
            background: 'linear-gradient(135deg, #134e5e 0%, #71b280 100%)',
            waveColors: ['rgba(255,255,255,0.25)', 'rgba(255,255,255,0.2)', 'rgba(255,255,255,0.15)', 'rgba(255,255,255,0.1)']
        },
        purple: {
            background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            waveColors: ['rgba(255,255,255,0.3)', 'rgba(255,255,255,0.25)', 'rgba(255,255,255,0.2)', 'rgba(255,255,255,0.15)']
        },
        mint: {
            background: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
            waveColors: ['rgba(255,255,255,0.5)', 'rgba(255,255,255,0.4)', 'rgba(255,255,255,0.3)', 'rgba(255,255,255,0.2)']
        }
    };
    
    // Speed configurations
    const speeds = {
        slow: { base: 15, variance: 5 },
        normal: { base: 10, variance: 3 },
        fast: { base: 6, variance: 2 }
    };
    
    // Intensity configurations
    const intensities = {
        low: { amplitude: 20, frequency: 0.02 },
        medium: { amplitude: 40, frequency: 0.015 },
        high: { amplitude: 60, frequency: 0.01 }
    };
    
    // Handle window resize
    useEffect(() => {
        const handleResize = () => {
            setDimensions({
                width: window.innerWidth,
                height: window.innerHeight
            });
        };
        
        window.addEventListener('resize', handleResize);
        return () => window.removeEventListener('resize', handleResize);
    }, []);
    
    // Generate wave path
    const generateWavePath = (amplitude, frequency, phase, width, height) => {
        const points = [];
        const steps = Math.max(50, width / 10);
        
        for (let i = 0; i <= steps; i++) {
            const x = (i / steps) * width;
            const y = height - (amplitude * Math.sin((x * frequency) + phase));
            points.push(`${x},${y}`);
        }
        
        // Close the path
        points.push(`${width},${height}`);
        points.push(`0,${height}`);
        
        return `M${points.join(' L')}Z`;
    };
    
    // Wave animation component
    const AnimatedWave = ({ layer, theme, speed, intensity }) => {
        const [phase, setPhase] = useState(0);
        
        useEffect(() => {
            const speedConfig = speeds[speed];
            const intensityConfig = intensities[intensity];
            const duration = speedConfig.base + (Math.random() * speedConfig.variance);
            
            const animate = () => {
                setPhase(prev => prev + 0.02);
                animationRef.current = requestAnimationFrame(animate);
            };
            
            animationRef.current = requestAnimationFrame(animate);
            
            return () => {
                if (animationRef.current) {
                    cancelAnimationFrame(animationRef.current);
                }
            };
        }, [speed, intensity]);
        
        const themeConfig = themes[theme];
        const intensityConfig = intensities[intensity];
        const layerAmplitude = intensityConfig.amplitude * (1 - layer * 0.2);
        const layerFrequency = intensityConfig.frequency * (1 + layer * 0.001);
        const layerPhase = phase + (layer * Math.PI * 0.5);
        
        return React.createElement('path', {
            d: generateWavePath(
                layerAmplitude,
                layerFrequency,
                layerPhase,
                dimensions.width,
                dimensions.height
            ),
            fill: themeConfig.waveColors[layer] || 'rgba(255,255,255,0.1)',
            style: {
                transform: `translateY(${layer * 10}px)`,
                transition: 'all 0.3s ease'
            }
        });
    };
    
    // Floating elements component
    const FloatingElements = () => {
        const elements = Array.from({ length: 8 }, (_, i) => ({
            id: i,
            size: 5 + Math.random() * 20,
            left: Math.random() * 100,
            delay: Math.random() * 10,
            duration: 10 + Math.random() * 10
        }));
        
        return React.createElement('div', {
            className: 'floating-elements-react',
            style: {
                position: 'absolute',
                top: 0,
                left: 0,
                width: '100%',
                height: '100%',
                pointerEvents: 'none',
                overflow: 'hidden'
            }
        }, elements.map(element => 
            React.createElement('div', {
                key: element.id,
                className: 'floating-element-react',
                style: {
                    position: 'absolute',
                    width: `${element.size}px`,
                    height: `${element.size}px`,
                    left: `${element.left}%`,
                    bottom: '-20px',
                    background: 'rgba(255, 255, 255, 0.2)',
                    borderRadius: '50%',
                    animation: `floatUp ${element.duration}s infinite linear ${element.delay}s`
                }
            })
        ));
    };
    
    // Main component render
    return React.createElement('div', {
        className: `react-wave-container ${className}`,
        style: {
            position: 'fixed',
            top: 0,
            left: 0,
            width: '100%',
            height: '100%',
            zIndex: -1,
            background: themes[theme].background,
            overflow: 'hidden'
        }
    }, [
        // SVG waves
        React.createElement('svg', {
            key: 'waves',
            width: dimensions.width,
            height: dimensions.height,
            style: {
                position: 'absolute',
                bottom: 0,
                left: 0
            }
        }, Array.from({ length: layers }, (_, i) =>
            React.createElement(AnimatedWave, {
                key: i,
                layer: i,
                theme: theme,
                speed: speed,
                intensity: intensity
            })
        )),
        
        // Floating elements
        showFloatingElements && React.createElement(FloatingElements, { key: 'floating' })
    ]);
};

// CSS for floating animation (inject into document)
const injectFloatingCSS = () => {
    if (document.getElementById('react-wave-styles')) return;
    
    const style = document.createElement('style');
    style.id = 'react-wave-styles';
    style.textContent = `
        @keyframes floatUp {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }
        
        .floating-element-react {
            backdrop-filter: blur(1px);
        }
        
        @media (prefers-reduced-motion: reduce) {
            .floating-element-react {
                animation: none !important;
            }
        }
    `;
    document.head.appendChild(style);
};

// Wave Background Manager - Higher Order Component
const WaveBackgroundManager = ({ children, config = {} }) => {
    const [currentTheme, setCurrentTheme] = useState(config.theme || 'ocean');
    const [isVisible, setIsVisible] = useState(true);
    
    useEffect(() => {
        injectFloatingCSS();
    }, []);
    
    // Theme switching function
    const switchTheme = (newTheme) => {
        setCurrentTheme(newTheme);
    };
    
    // Toggle visibility
    const toggleWaves = () => {
        setIsVisible(!isVisible);
    };
    
    return React.createElement(React.Fragment, null, [
        // Wave background
        isVisible && React.createElement(ReactWaveBackground, {
            key: 'wave-bg',
            theme: currentTheme,
            intensity: config.intensity || 'medium',
            speed: config.speed || 'normal',
            layers: config.layers || 4,
            showFloatingElements: config.showFloatingElements !== false
        }),
        
        // Content with wave controls
        React.createElement('div', {
            key: 'content',
            style: { position: 'relative', zIndex: 1 }
        }, [
            // Wave control panel (optional)
            config.showControls && React.createElement('div', {
                key: 'controls',
                className: 'wave-controls',
                style: {
                    position: 'fixed',
                    top: '20px',
                    right: '20px',
                    background: 'rgba(255, 255, 255, 0.9)',
                    padding: '15px',
                    borderRadius: '10px',
                    boxShadow: '0 4px 15px rgba(0, 0, 0, 0.1)',
                    zIndex: 1000
                }
            }, [
                React.createElement('div', {
                    key: 'theme-controls',
                    style: { marginBottom: '10px' }
                }, [
                    React.createElement('label', {
                        key: 'label',
                        style: { fontSize: '0.9rem', fontWeight: '600', marginBottom: '5px', display: 'block' }
                    }, 'Wave Theme:'),
                    React.createElement('select', {
                        key: 'select',
                        value: currentTheme,
                        onChange: (e) => switchTheme(e.target.value),
                        style: {
                            width: '100%',
                            padding: '5px 10px',
                            borderRadius: '5px',
                            border: '1px solid #ddd'
                        }
                    }, [
                        React.createElement('option', { key: 'ocean', value: 'ocean' }, 'Ocean'),
                        React.createElement('option', { key: 'sunset', value: 'sunset' }, 'Sunset'),
                        React.createElement('option', { key: 'forest', value: 'forest' }, 'Forest'),
                        React.createElement('option', { key: 'purple', value: 'purple' }, 'Purple'),
                        React.createElement('option', { key: 'mint', value: 'mint' }, 'Mint')
                    ])
                ]),
                React.createElement('button', {
                    key: 'toggle',
                    onClick: toggleWaves,
                    style: {
                        width: '100%',
                        padding: '8px 15px',
                        backgroundColor: isVisible ? '#ff6b6b' : '#51cf66',
                        color: 'white',
                        border: 'none',
                        borderRadius: '5px',
                        cursor: 'pointer',
                        fontSize: '0.9rem',
                        fontWeight: '600'
                    }
                }, isVisible ? 'Hide Waves' : 'Show Waves')
            ]),
            
            // Main content
            children
        ])
    ]);
};

// Export components for global use
window.ReactWaveBackground = ReactWaveBackground;
window.WaveBackgroundManager = WaveBackgroundManager;