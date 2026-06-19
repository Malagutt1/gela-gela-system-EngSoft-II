/* ==========================================================================
   Gela-Gela Landing Page - Main Application with Product Carousel
   ========================================================================== */

// ==========================================
// PRODUTOS DATABASE
// ==========================================
const products = [
    {
        id: 0,
        name: 'Premium Strawberry',
        description: 'Uma explosão de sabor fofo em cada mordida! A Premium Strawberry é a casquinha de waffle crocante perfeita!',
        moreInfo: 'recheada com um sorvete de morango super cremoso, granulado colorido e um waffer fofinho para um toque especial. Crocante por fora e apaixonante por dentro! ✨🍓🍦 (Disponível em vários sabores).',
        image: 'assets/copo-branco-azul.png'
    },
    {
        id: 1,
        name: 'Shakeberry',
        description: 'Puro amor em forma de bebida! Nosso Shakeberry é um milkshake de morango incrivelmente cremoso e super smooth!',
        moreInfo: 'Servido no copo fofo com domo e canudo listrado. Perfeito para refrescar e sorrir a cada gole. 🍓✨ (Disponível em vários sabores).',
        image: '/ASSETS/IMG/Milkshake.png'
    },
    {
        id: 2,  
        name: 'Baldão Gela-Gela',
        description: 'Para os momentos de pura felicidade compartilhada! O nosso Baldão vem recheado com o seu sabor favorito!',
        moreInfo: 'pronto para ser o rei da sobremesa em família ou com amigos. Com um design moderno e tampa charmosa, é o seu estoque de sorrisos garantido! 🎉✨ (Disponível em vários sabores).',
        image: 'assets/copo-rosa-coral.png'
    },
    {
        id: 3,
        name: 'Gela-Ccino',
        description: 'A dose perfeita de energia com aquele toque geladinho que a gente ama!',
        moreInfo: 'O Gela-Ccino é a nossa versão super refrescante do cappuccino clássico, combinando camadas de café espresso, leite super cremoso, muito gelo e uma nuvem de espuma fofinha polvilhada com cacau. O abraço gelado que o seu dia estava pedindo para você acordar e sorrir! ✨🤎 (Disponível com opções de xaropes em vários sabores).',
        image: 'assets/copo-rosa-coral.png'
    }
];

let currentProduct = 0;
let scrollTimeout;
let isScrolling = false;

// ==========================================
// INICIALIZAÇÃO
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    initScrollNavigation();
    initMenuButton();
    initSocialLinks();
    initProductIndicators();
    initCTAButton();
    updateProductInfo(0);
}

// ==========================================
// SCROLL NAVIGATION (Horizontal Scroll)
// ==========================================
function initScrollNavigation() {
    let scrollDelta = 0;

    document.addEventListener('wheel', function(e) {
        // Previne scroll padrão
        e.preventDefault();

        if (isScrolling) return;

        // Acumula o delta do scroll
        scrollDelta += e.deltaY;

        // Threshold para mudança de produto (100px de scroll)
        if (Math.abs(scrollDelta) > 100) {
            if (scrollDelta > 0) {
                // Scroll down = próximo produto
                nextProduct();
            } else {
                // Scroll up = produto anterior
                previousProduct();
            }
            scrollDelta = 0;
        }
    }, { passive: false });
}

// Próximo produto
function nextProduct() {
    if (isScrolling) return;
    
    isScrolling = true;
    currentProduct = (currentProduct + 1) % products.length;
    transitionProduct();

    setTimeout(() => {
        isScrolling = false;
    }, 800);
}

// Produto anterior
function previousProduct() {
    if (isScrolling) return;
    
    isScrolling = true;
    currentProduct = (currentProduct - 1 + products.length) % products.length;
    transitionProduct();

    setTimeout(() => {
        isScrolling = false;
    }, 800);
}

// Transição de produto
function transitionProduct() {
    const slides = document.querySelectorAll('.product-slide');
    
    slides.forEach((slide, index) => {
        slide.classList.remove('active', 'prev');
        
        if (index === currentProduct) {
            slide.classList.add('active');
        } else if (index < currentProduct) {
            slide.classList.add('prev');
        }
    });

    updateProductInfo(currentProduct);
    updateIndicators(currentProduct);

    // Confete ao mudar produto
    createConfetti(window.innerWidth / 2, window.innerHeight / 2);
    playSound('product-change');
}

// ==========================================
// ATUALIZAR INFORMAÇÕES DO PRODUTO
// ==========================================
function updateProductInfo(index) {
    const product = products[index];
    const productName = document.getElementById('productName');
    const productDescription = document.getElementById('productDescription');
    const moreInfoText = document.getElementById('moreInfoText');

    // Animação de mudança de texto
    gsap.to([productName, productDescription, moreInfoText], {
        duration: 0.3,
        opacity: 0,
        y: -10
    });

    setTimeout(() => {
        productName.textContent = product.name;
        productName.classList.add('changing');
        productDescription.textContent = product.description;
        moreInfoText.textContent = product.moreInfo;

        gsap.to([productName, productDescription, moreInfoText], {
            duration: 0.4,
            opacity: 1,
            y: 0,
            ease: 'back.out'
        });

        setTimeout(() => {
            productName.classList.remove('changing');
        }, 400);
    }, 300);
}

// ==========================================
// PRODUCT INDICATORS
// ==========================================
function initProductIndicators() {
    const indicators = document.querySelectorAll('.indicator');
    
    indicators.forEach(indicator => {
        indicator.addEventListener('click', function() {
            const productIndex = parseInt(this.getAttribute('data-product'));
            if (productIndex !== currentProduct) {
                isScrolling = true;
                currentProduct = productIndex;
                transitionProduct();

                setTimeout(() => {
                    isScrolling = false;
                }, 800);
            }
        });
    });
}

function updateIndicators(index) {
    const indicators = document.querySelectorAll('.indicator');
    indicators.forEach(indicator => {
        indicator.classList.remove('active');
    });
    indicators[index].classList.add('active');
}

// ==========================================
// MENU BUTTON
// ==========================================
function initMenuButton() {
    const menuBtn = document.getElementById('menuBtn');

    if (menuBtn) {
        menuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            toggleMenu();
        });
    }
}

function toggleMenu() {
    gsap.to('.menu-btn', {
        duration: 0.3,
        rotateZ: 90,
        ease: 'back.out'
    });

    setTimeout(() => {
        gsap.to('.menu-btn', {
            duration: 0.3,
            rotateZ: 0,
            ease: 'back.out'
        });
    }, 300);
}

// ==========================================
// SOCIAL LINKS
// ==========================================
function initSocialLinks() {
    const socialLinks = document.querySelectorAll('.social-link');

    socialLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const social = this.getAttribute('data-social');
            handleSocialClick(social);
        });

        link.addEventListener('mouseenter', function() {
            gsap.to(this, {
                duration: 0.3,
                scale: 1.15,
                rotateZ: 10
            });
        });

        link.addEventListener('mouseleave', function() {
            gsap.to(this, {
                duration: 0.3,
                scale: 1,
                rotateZ: 0
            });
        });
    });
}

function handleSocialClick(social) {
    const urls = {
        facebook: 'https://facebook.com/gelagelasorvetes',
        instagram: 'https://instagram.com/gelagelasorvetes',
        whatsapp: 'https://wa.me/5511999999999'
    };

    const url = urls[social];
    if (url) {
        window.open(url, '_blank');
    }
}

// ==========================================
// CTA BUTTON
// ==========================================
function initCTAButton() {
    const ctaBtn = document.querySelector('.btn-cta');

    if (ctaBtn) {
        ctaBtn.addEventListener('click', function(e) {
            e.preventDefault();

            gsap.timeline()
                .to(this, {
                    duration: 0.1,
                    scale: 0.95
                })
                .to(this, {
                    duration: 0.2,
                    scale: 1,
                    ease: 'back.out'
                });

            playSound('click');
        });

        ctaBtn.addEventListener('mouseenter', function() {
            gsap.to(this, {
                duration: 0.3,
                y: -4,
                boxShadow: '0 16px 32px rgba(198, 116, 106, 0.4)'
            });
        });

        ctaBtn.addEventListener('mouseleave', function() {
            gsap.to(this, {
                duration: 0.3,
                y: 0,
                boxShadow: '0 12px 24px rgba(198, 116, 106, 0.3)'
            });
        });
    }
}

// ==========================================
// CONFETTI EFFECT
// ==========================================
function createConfetti(x, y) {
    const confettiPieces = 15;

    for (let i = 0; i < confettiPieces; i++) {
        const confetti = document.createElement('div');
        confetti.style.cssText = `
            position: fixed;
            left: ${x}px;
            top: ${y}px;
            width: 10px;
            height: 10px;
            background: ${getRandomProductColor()};
            border-radius: 50%;
            pointer-events: none;
            z-index: 1000;
        `;

        document.body.appendChild(confetti);

        gsap.to(confetti, {
            duration: 1.5,
            x: (Math.random() - 0.5) * 200,
            y: Math.random() * 200 + 100,
            opacity: 0,
            scale: 0.5,
            ease: 'power2.out',
            onComplete: () => confetti.remove()
        });
    }
}

// ==========================================
// SOUND EFFECTS
// ==========================================
function playSound(soundName) {
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();

        const sounds = {
            'product-change': () => {
                const oscillator = audioContext.createOscillator();
                const gain = audioContext.createGain();

                oscillator.connect(gain);
                gain.connect(audioContext.destination);

                oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
                oscillator.frequency.exponentialRampToValueAtTime(400, audioContext.currentTime + 0.2);
                oscillator.type = 'sine';

                gain.gain.setValueAtTime(0.2, audioContext.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);

                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.2);
            },
            'click': () => {
                const oscillator = audioContext.createOscillator();
                const gain = audioContext.createGain();

                oscillator.connect(gain);
                gain.connect(audioContext.destination);

                oscillator.frequency.setValueAtTime(600, audioContext.currentTime);
                oscillator.frequency.exponentialRampToValueAtTime(200, audioContext.currentTime + 0.1);
                oscillator.type = 'triangle';

                gain.gain.setValueAtTime(0.2, audioContext.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);

                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.1);
            }
        };

        if (sounds[soundName]) {
            sounds[soundName]();
        }
    } catch (e) {
        console.warn('Audio Context não disponível:', e.message);
    }
}

// ==========================================
// HELPERS
// ==========================================
function getRandomProductColor() {
    const colors = [
        'rgb(198, 116, 106)',  // Rosa/Coral
        'rgb(17, 65, 123)',    // Azul
        'rgb(250, 206, 225)',  // Rosa claro
        'rgb(255, 215, 0)',    // Ouro
        'rgb(245, 222, 179)'   // Bege
    ];

    return colors[Math.floor(Math.random() * colors.length)];
}

// ==========================================
// EXPORT
// ==========================================
window.GelaGelaApp = {
    nextProduct,
    previousProduct,
    transitionProduct,
    createConfetti,
    playSound,
    getRandomProductColor
};
