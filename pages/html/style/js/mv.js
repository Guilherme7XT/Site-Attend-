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

    // Animação de scroll para os elementos com a classe 'animate-on-scroll'
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('in-view');
            }
        });
    }, {
        threshold: 0.1 // O elemento é considerado visível quando 10% dele está na tela
    });

    // Adiciona a classe para habilitar as animações apenas se o JS estiver ativo
    document.body.classList.add('js-animations-enabled');

    const elementsToAnimate = document.querySelectorAll('.animate-on-scroll');
    elementsToAnimate.forEach(el => {
        observer.observe(el);
    });

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

    // Inicializa as partículas na seção "meio", se o elemento existir
    if (document.getElementById('particles-js-meio')) {
        particlesJS('particles-js-meio', particleConfig);
    }

    // === PROCESSAMENTO DO FORMULÁRIO DE CONTATO ===
    const form = document.getElementById('contactForm');
    if (form) {
        const submitBtn = document.getElementById('submitBtn');
        const successMessage = document.getElementById('successMessage');

        // Campos do formulário
        const nameInput = document.getElementById('nome');
        const contactInput = document.getElementById('contato');
        const emailInput = document.getElementById('email');

        // Elementos de erro
        const nameError = document.getElementById('nameError');
        const contactError = document.getElementById('contactError');
        const emailError = document.getElementById('emailError');

        // Função para validar e-mail
        function isValidEmail(email) {
            const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(String(email).toLowerCase());
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            let isValid = true;

            // Validação do nome
            if (nameInput.value.trim() === '') {
                if (nameError) nameError.style.display = 'block';
                isValid = false;
            } else {
                if (nameError) nameError.style.display = 'none';
            }

            // Validação do contato
            if (contactInput.value.trim() === '') {
                if (contactError) contactError.style.display = 'block';
                isValid = false;
            } else {
                if (contactError) contactError.style.display = 'none';
            }

            // Validação do e-mail
            if (!isValidEmail(emailInput.value.trim())) {
                if (emailError) emailError.style.display = 'block';
                isValid = false;
            } else {
                if (emailError) emailError.style.display = 'none';
            }

            if (isValid) {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = 'Enviando... <span class="loading"></span>';
                }

                // Preparar dados do formulário
                const formData = new FormData(form);

                // Enviar via AJAX
                fetch('../../process_form.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (successMessage) {
                            successMessage.style.display = 'block';
                            successMessage.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                        }
                        form.reset();
                    } else {
                        // Mostrar erros se houver
                        if (data.errors) {
                            alert('Erro: ' + data.errors.join(', '));
                        }
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao enviar formulário. Tente novamente.');
                })
                .finally(() => {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = 'Enviar';
                    }
                    
                    setTimeout(function() {
                        if (successMessage) successMessage.style.display = 'none';
                    }, 5000);
                });
            }
        });
    }
});