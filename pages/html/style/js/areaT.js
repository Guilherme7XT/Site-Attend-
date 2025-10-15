document.addEventListener('DOMContentLoaded', function () {
    // === FUNCIONALIDADE DO MENU MOBILE ===
    const menuToggle = document.getElementById('menuToggle');
    const menuToggleScroll = document.getElementById('menuToggleScroll');
    const navMenu = document.getElementById('navMenu');
    const navMenuScroll = document.getElementById('navMenuScroll');
    const headerMain = document.querySelector('.header-main');
    const headerScroll = document.querySelector('.header-on-scroll');

    // Função para alternar o menu
    function toggleMenu(menu, toggle) {
        if (menu && toggle) {
            menu.classList.toggle('active');
            toggle.classList.toggle('active');
        }
    }

    // Função para fechar o menu
    function closeMenu() {
        if (navMenu) {
            navMenu.classList.remove('active');
            menuToggle.classList.remove('active');
        }
        if (navMenuScroll) {
            navMenuScroll.classList.remove('active');
            menuToggleScroll.classList.remove('active');
        }
    }

    // Event listeners para os botões do menu
    if (menuToggle) {
        menuToggle.addEventListener('click', () => toggleMenu(navMenu, menuToggle));
    }

    if (menuToggleScroll) {
        menuToggleScroll.addEventListener('click', () => toggleMenu(navMenuScroll, menuToggleScroll));
    }

    // Fechar menu ao clicar nos links
    const navLinks = document.querySelectorAll('.nav-menu a');
    navLinks.forEach(link => {
        link.addEventListener('click', closeMenu);
    });

    // Fechar menu ao clicar fora dele
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.nav-menu') && !e.target.closest('.menu-toggle')) {
            closeMenu();
        }
    });

    // === FUNCIONALIDADE DO HEADER SCROLL ===
    let lastScrollTop = 0;
    let scrollTimeout;

    window.addEventListener('scroll', () => {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // Limpa o timeout anterior
        clearTimeout(scrollTimeout);
        
        // Adiciona um pequeno delay para evitar flickering
        scrollTimeout = setTimeout(() => {
            if (scrollTop > 100) {
                // Mostra o header secundário
                if (headerScroll) {
                    headerScroll.classList.add('visible');
                }
                if (headerMain) {
                    headerMain.classList.add('hidden-by-scroll');
                }
            } else {
                // Esconde o header secundário
                if (headerScroll) {
                    headerScroll.classList.remove('visible');
                }
                if (headerMain) {
                    headerMain.classList.remove('hidden-by-scroll');
                }
            }
            
            lastScrollTop = scrollTop;
        }, 10);
    });

    // === ANIMAÇÕES DE SCROLL ===
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });

    // Adiciona a classe para habilitar as animações
    document.body.classList.add('js-animations-enabled');

    // Aguarda um pouco para garantir que o DOM esteja pronto
    setTimeout(() => {
        const elementsToAnimate = document.querySelectorAll('.animate-on-scroll');
        elementsToAnimate.forEach(el => {
            observer.observe(el);
        });
        
        // Força a animação dos elementos que já estão visíveis
        elementsToAnimate.forEach(el => {
            const rect = el.getBoundingClientRect();
            if (rect.top < window.innerHeight && rect.bottom > 0) {
                el.classList.add('is-visible');
            }
        });
    }, 100);

    // Configuração das partículas
    const particleConfig = {
        "particles": {
            "number": {
                "value": 80,
                "density": {
                    "enable": true,
                    "value_area": 800
                }
            },
            "color": {
                "value": "#f6b21b" // Cor amarela do site (--secondary)
            },
            "shape": {
                "type": "circle",
            },
            "opacity": {
                "value": 0.5,
                "random": true,
            },
            "size": {
                "value": 3,
                "random": true,
            },
            "line_linked": {
                "enable": true,
                "distance": 150,
                "color": "#f6b21b", // Cor amarela do site (--secondary)
                "opacity": 0.4,
                "width": 1
            },
            "move": {
                "enable": true,
                "speed": 2,
                "direction": "none",
                "random": false,
                "straight": false,
                "out_mode": "out",
                "bounce": false,
            }
        },
        "interactivity": {
            "detect_on": "canvas",
            "events": {
                "onhover": {
                    "enable": true,
                    "mode": "repulse"
                },
                "onclick": {
                    "enable": true,
                    "mode": "push"
                },
                "resize": true
            },
            "modes": {
                "grab": {
                    "distance": 400,
                    "line_linked": {
                        "opacity": 1
                    }
                },
                "bubble": {
                    "distance": 400,
                    "size": 40,
                    "duration": 2,
                    "opacity": 8,
                    "speed": 3
                },
                "repulse": {
                    "distance": 100,
                    "duration": 0.4
                },
                "push": {
                    "particles_nb": 4
                },
                "remove": {
                    "particles_nb": 2
                }
            }
        },
        "retina_detect": true
    };

    // Inicializa as partículas no banner, se o elemento existir
    if (document.getElementById('particles-js-banner')) {
        particlesJS('particles-js-banner', particleConfig);
    }
});
