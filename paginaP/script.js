// --- LOADER LOGIC ---
// Função para esconder e remover o loader, prevenindo que ele fique travado.
function hideLoader() {
    const loader = document.getElementById('loader');
    if (loader && !loader.classList.contains('hidden')) {
        loader.classList.add('hidden');
        // Após a transição do CSS, remove o elemento do DOM para liberar recursos
        setTimeout(() => {
            if (loader.parentNode) {
                loader.parentNode.removeChild(loader);
            }
        }, 800); // Esta duração deve corresponder à transição no CSS
        // Limpa os timeouts de segurança para evitar chamadas desnecessárias
        clearTimeout(window.loaderTimeout);
        window.removeEventListener('load', hideLoader);
    }
}

// Tenta esconder o loader em diferentes estágios para garantir que ele feche.
document.addEventListener('DOMContentLoaded', hideLoader);
window.addEventListener('load', hideLoader); // Fallback para imagens e outros recursos
// Um último recurso de segurança, armazenado em uma variável para poder ser limpo
window.loaderTimeout = setTimeout(hideLoader, 3000);

// --- HEADER SCROLL & BOTÃO SCROLL-TO-TOP ---
window.addEventListener('scroll', function() {
    const mainHeader = document.querySelector('.header-main');
    const scrolledHeader = document.querySelector('.header-on-scroll');
    const scrollToTopBtn = document.getElementById('scrollToTopBtn');

    if (window.scrollY > 50) {
        // Mostra o header secundário e esconde o principal
        if (scrolledHeader) scrolledHeader.classList.add('visible');
        if (mainHeader) mainHeader.classList.add('hidden-by-scroll');
    } else {
        // Esconde o header secundário e mostra o principal
        if (scrolledHeader) scrolledHeader.classList.remove('visible');
        if (mainHeader) mainHeader.classList.remove('hidden-by-scroll');
    }

    // Lógica para o botão "voltar ao topo"
    if (window.scrollY > 400) { // Aparece depois de rolar 400px
        if (scrollToTopBtn) {
            scrollToTopBtn.classList.add('visible');
        }
    } else {
        if (scrollToTopBtn) {
            scrollToTopBtn.classList.remove('visible');
        }
    }
});

// --- TODAS AS INICIALIZAÇÕES NO DOM CONTENT LOADED ---
document.addEventListener('DOMContentLoaded', function() {

    // --- LÓGICA DO MENU HAMBÚRGUER ---
    const menuToggles = document.querySelectorAll('.menu-toggle');
    const navs = document.querySelectorAll('nav');

    menuToggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
            // Encontra a nav correspondente dentro do mesmo header
            const parentHeader = toggle.closest('header');
            const nav = parentHeader.querySelector('nav');

            if (nav) {
                nav.classList.toggle('active');
                toggle.classList.toggle('active');
            }
        });
    });

    // Fecha o menu ao clicar em um link (para navegação em página única)
    navs.forEach(nav => {
        nav.addEventListener('click', (e) => {
            if (e.target.tagName === 'A' && nav.classList.contains('active')) {
                navs.forEach(n => n.classList.remove('active'));
                menuToggles.forEach(t => t.classList.remove('active'));
            }
        });
    });

    // Animação de entrada para a seção Banner (antigo Hero)
    const bannerSection = document.querySelector('.banner');
    if (bannerSection) {
        const bannerObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    // Adiciona a classe para iniciar as transições de entrada
                    entry.target.classList.add('is-visible');
                } else {
                    // Remove a classe para reverter os elementos ao estado inicial
                    entry.target.classList.remove('is-visible');
                }
            });
        }, {
            threshold: 0.5
        }); // A animação começa quando 50% da seção estiver visível

        bannerObserver.observe(bannerSection);
    }

    // Animação de entrada única para outras seções
    const fadeElements = document.querySelectorAll('.about-title, .about-text, .about-image, .section-subtitle, .title-service');
    const fadeObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'fadeUp 0.8s ease forwards';
            } else {
                // Reseta a animação para que ela possa ser acionada novamente
                entry.target.style.animation = 'none';
                entry.target.style.opacity = '0';
                entry.target.style.transform = 'translateY(20px)';
            }
        });
    }, {
        threshold: 0.1
    });

    fadeElements.forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        fadeObserver.observe(element);
    });

    // Animação de entrada para a Seção de Imagem Introdutória (Área do Técnico)
    const introSection = document.querySelector('.intro-image-section');
    if (introSection) {
        const introObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                } else {
                    // Reseta a animação para que ela possa ser acionada novamente
                    entry.target.classList.remove('is-visible');
                }
            });
        }, { threshold: 0.3 }); // Inicia quando 30% da seção estiver visível
        introObserver.observe(introSection);
    }

    // Animação de entrada "trenzinho" para o carrossel de serviços
    const serviceCarousel = document.querySelector('.feature-carousel-wrapper');
    if (serviceCarousel) {
        const cards = Array.from(serviceCarousel.querySelectorAll('.feature-card'));
        const featureContainer = serviceCarousel.querySelector('.feature');

        const serviceObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    // 1. Adiciona a classe ao container para tornar os cards visíveis
                    entry.target.classList.add('is-visible');

                    // 2. Define o atraso individual para cada card criar o efeito "trenzinho"
                    cards.forEach((card, index) => {
                        card.style.transitionDelay = `${index * 0.15}s`;
                    });

                    // 3. Calcula o tempo total da animação de entrada para o primeiro conjunto de cards
                    const uniqueCardsCount = cards.length / 2;
                    const lastCardDelay = (uniqueCardsCount - 1) * 0.15; // Atraso do último card visível
                    const transitionDuration = 0.6; // Duração da transição do CSS
                    const totalAnimationTime = (lastCardDelay + transitionDuration) * 1000;

                    // 4. Adiciona a classe de rolagem após a animação de entrada terminar
                    setTimeout(() => {
                        if (featureContainer && window.innerWidth >= 1200) {
                            featureContainer.classList.add('scrolling-active');
                        }
                    }, totalAnimationTime + 500); // Adiciona um buffer de 500ms antes de começar a rolar

                    // 5. Para de observar para a animação não repetir
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.2 }); // Inicia quando 20% do carrossel estiver visível

        serviceObserver.observe(serviceCarousel);
    }

    // Validação e envio de formulário do rodapé
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

                // Simulação de envio
                setTimeout(() => {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = 'Enviar';
                    }
                    if (successMessage) {
                        successMessage.style.display = 'block';
                        successMessage.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }
                    form.reset();

                    setTimeout(function() {
                        if (successMessage) successMessage.style.display = 'none';
                    }, 5000);

                }, 1500); // Simula um delay de rede de 1.5s
            }
        });
    }

    // Animação de entrada para a seção do mapa
    const mapaSection = document.querySelector('.mapa#mental');
    if (mapaSection) {
        const mapaObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                } else {
                    // Opcional: remover a classe para re-animar na próxima vez que entrar na tela
                    entry.target.classList.remove('is-visible');
                }
            });
        }, {
            threshold: 0.2 // A animação começa quando 20% da seção estiver visível
        });
        mapaObserver.observe(mapaSection);

    }

    // Animação para a linha decorativa da imagem "Quem Somos"
    const aboutImage = document.querySelector('.about-image');
    if (aboutImage) {
        const lineObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('line-visible');
                } else {
                    entry.target.classList.remove('line-visible');
                }
            });
        }, { threshold: 0.8 }); // Aciona quando 80% do elemento estiver visível
        lineObserver.observe(aboutImage);
    }

    // Animação para a linha que vira para cima na seção "Quem Somos"
    const animatedLineContainer = document.querySelector('.about'); // O container da grade
    const animatedLineTrigger = document.querySelector('.about-image'); // O gatilho da animação

    if (animatedLineContainer && animatedLineTrigger) {
        const animatedLineObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    // Adiciona a classe ao container pai para iniciar as animações
                    animatedLineContainer.classList.add('start-line-animation');
                } else {
                    // Remove a classe para resetar a animação quando o elemento sai da tela
                    animatedLineContainer.classList.remove('start-line-animation');
                }
            });
        }, {
            threshold: 0.9 // Gatilho quando 90% da imagem estiver visível
        });
        animatedLineObserver.observe(animatedLineTrigger);
    }
});