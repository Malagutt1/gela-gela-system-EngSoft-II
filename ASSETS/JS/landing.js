/* ==========================================================================
   Gela-Gela Landing Page - Main Application com Anime.js
   ========================================================================== */

const products = [
    {
        id: 0,
        name: 'Premium Strawberry',
        description: 'Uma explosão de sabor fofo em cada mordida! A Premium Strawberry é a casquinha de waffle crocante perfeita!',
        moreInfo: 'recheada com um sorvete de morango super cremoso, granulado colorido e um waffer fofinho para um toque especial. Crocante por fora e apaixonante por dentro! ✨🍓🍦 (Disponível em vários sabores).',
    },
    {
        id: 1,
        name: 'Shakeberry',
        description: 'Puro amor em forma de bebida! Nosso Shakeberry é um milkshake de morango incrivelmente cremoso e super smooth!',
        moreInfo: 'Servido no copo fofo com domo e canudo listrado. Perfeito para refrescar e sorrir a cada gole. 🍓✨ (Disponível em vários sabores).',
    },
    {
        id: 2,  
        name: 'Baldão Gela-Gela',
        description: 'Para os momentos de pura felicidade compartilhada! O nosso Baldão vem recheado com o seu sabor favorito!',
        moreInfo: 'pronto para ser o rei da sobremesa em família ou com amigos. Com um design moderno e tampa charmosa, é o seu estoque de sorrisos garantido! 🎉✨ (Disponível em vários sabores).',
    },
    {
        id: 3,
        name: 'Gela-Ccino',
        description: 'A dose perfeita de energia com aquele toque geladinho que a gente ama!',
        moreInfo: 'O Gela-Ccino é a nossa versão super refrescante do cappuccino clássico, combinando camadas de café espresso, leite super cremoso, muito gelo e uma nuvem de espuma fofinha polvilhada com cacau. O abraço gelado que o seu dia estava pedindo para você acordar e sorrir! ✨🤎 (Disponível com opções de xaropes em vários sabores).',
    }
];

// Estado global
let state = {
    currentProduct: 0,
    section: 'carousel',
    isAnimating: false
};

// ==========================================
// INICIALIZAÇÃO
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    document.body.style.overflow = 'hidden';
    setupScrollListener();
    setupMenuButton();
    setupSocialLinks();
    setupIndicators();
    setupCTAButton();
    updateProductDisplay();
});

// ==========================================
// SCROLL LISTENER
// ==========================================
let scrollDelta = 0;

function setupScrollListener() {
    document.addEventListener('wheel', (e) => {
        if (state.isAnimating) return;
        e.preventDefault();
        
        scrollDelta += e.deltaY;
        
        if (scrollDelta > 100) {
            scrollDown();
            scrollDelta = 0;
        } else if (scrollDelta < -100) {
            scrollUp();
            scrollDelta = 0;
        }
    }, { passive: false });

    document.addEventListener('keydown', (e) => {
        if (state.isAnimating) return;
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            scrollDown();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            scrollUp();
        }
    });
}

function scrollDown() {
    if (state.section === 'carousel') {
        if (state.currentProduct < products.length - 1) {
            state.currentProduct++;
            updateProductDisplay();
        } else {
            transitionToAbout();
        }
    } else if (state.section === 'about') {
        transitionToLocation();
    }
}

function scrollUp() {
    if (state.section === 'location') {
        transitionToAbout();
    } else if (state.section === 'about') {
        transitionToCarousel();
    } else if (state.section === 'carousel') {
        if (state.currentProduct > 0) {
            state.currentProduct--;
            updateProductDisplay();
        }
    }
}

// ==========================================
// TRANSIÇÕES COM ANIME.JS - SIMPLES E ESTÁVEL
// ==========================================
function transitionToAbout() {
    if (state.isAnimating || state.section === 'about') return;
    
    state.isAnimating = true;

    const appWindow = document.getElementById('appWindow');
    const aboutSection = document.getElementById('aboutSection');
    const locationSection = document.getElementById('locationSection');

    if (state.section === 'location') {
        locationSection.style.display = 'none';
        locationSection.classList.remove('visible');
    }

    appWindow.style.pointerEvents = 'none';
    
    // Fade out carousel
    anime({
        targets: appWindow,
        opacity: 0,
        duration: 800,
        easing: 'easeInOutQuad'
    });

    // Fade in about
    aboutSection.style.display = 'flex';
    aboutSection.classList.add('visible');
    anime({
        targets: aboutSection,
        opacity: 1,
        duration: 800,
        easing: 'easeInOutQuad',
        delay: 100,
        complete: () => {
            state.section = 'about';
            state.isAnimating = false;
            appWindow.style.display = 'none';
        }
    });
}

function transitionToLocation() {
    if (state.isAnimating || state.section === 'location') return;
    
    state.isAnimating = true;

    const aboutSection = document.getElementById('aboutSection');
    const locationSection = document.getElementById('locationSection');

    // Fade out about
    anime({
        targets: aboutSection,
        opacity: 0,
        duration: 800,
        easing: 'easeInOutQuad'
    });

    // Fade in location
    locationSection.style.display = 'flex';
    locationSection.classList.add('visible');
    anime({
        targets: locationSection,
        opacity: 1,
        duration: 800,
        easing: 'easeInOutQuad',
        delay: 100,
        complete: () => {
            state.section = 'location';
            state.isAnimating = false;
            aboutSection.style.display = 'none';
        }
    });
}

function transitionToCarousel() {
    if (state.isAnimating || state.section === 'carousel') return;
    
    state.isAnimating = true;

    const appWindow = document.getElementById('appWindow');
    const aboutSection = document.getElementById('aboutSection');
    const locationSection = document.getElementById('locationSection');

    aboutSection.classList.remove('visible');
    locationSection.classList.remove('visible');

    // Fade out seção
    anime({
        targets: [aboutSection, locationSection],
        opacity: 0,
        duration: 800,
        easing: 'easeInOutQuad'
    });

    // Fade in carousel
    appWindow.style.display = 'flex';
    appWindow.style.pointerEvents = 'auto';
    anime({
        targets: appWindow,
        opacity: 1,
        duration: 800,
        easing: 'easeInOutQuad',
        delay: 100,
        complete: () => {
            state.section = 'carousel';
            state.isAnimating = false;
            aboutSection.style.display = 'none';
            locationSection.style.display = 'none';
        }
    });
}

// ==========================================
// ATUALIZAR DISPLAY DO PRODUTO
// ==========================================
function updateProductDisplay() {
    state.isAnimating = true;

    const product = products[state.currentProduct];
    const productName = document.getElementById('productName');
    const productDescription = document.getElementById('productDescription');
    const moreInfoText = document.getElementById('moreInfoText');
    const slides = document.querySelectorAll('.product-slide');

    // Animar slides
    slides.forEach((slide, index) => {
        slide.classList.remove('active', 'prev');
        if (index === state.currentProduct) {
            slide.classList.add('active');
        } else if (index < state.currentProduct) {
            slide.classList.add('prev');
        }
    });

    // Fade out texto
    anime({
        targets: [productName, productDescription, moreInfoText],
        opacity: 0,
        duration: 300,
        easing: 'easeInOutQuad',
        complete: () => {
            productName.textContent = product.name;
            productDescription.textContent = product.description;
            moreInfoText.textContent = product.moreInfo;

            // Fade in texto
            anime({
                targets: [productName, productDescription, moreInfoText],
                opacity: 1,
                duration: 400,
                easing: 'easeOutQuad',
                complete: () => {
                    state.isAnimating = false;
                }
            });
        }
    });

    updateIndicators();
    createConfetti(window.innerWidth / 2, window.innerHeight / 2);
    playSound('change');
}

// ==========================================
// INDICADORES
// ==========================================
function setupIndicators() {
    document.querySelectorAll('.indicator').forEach(ind => {
        ind.addEventListener('click', function() {
            if (state.isAnimating || state.section !== 'carousel') return;
            const idx = parseInt(this.dataset.product);
            if (idx !== state.currentProduct) {
                state.currentProduct = idx;
                updateProductDisplay();
            }
        });
    });
}

function updateIndicators() {
    document.querySelectorAll('.indicator').forEach((ind, idx) => {
        if (idx === state.currentProduct) {
            anime({
                targets: ind,
                scale: 1.3,
                duration: 400,
                easing: 'easeOutElastic(1, .6)'
            });
        }
        ind.classList.toggle('active', idx === state.currentProduct);
    });
}

// ==========================================
// MENU
// ==========================================
const menuBtn = document.getElementById("menuBtn");
const menuPopup = document.getElementById("menuPopup");
const menuPopupClose = document.getElementById("menuPopupClose");

menuBtn.addEventListener("click", () => {
    menuPopup.classList.add("active");
});

menuPopupClose.addEventListener("click", () => {
    menuPopup.classList.remove("active");
});

menuPopup.addEventListener("click", (e) => {
    if (e.target === menuPopup) {
        menuPopup.classList.remove("active");
    }
});
// ==========================================
// SOCIAL
// ==========================================
function setupSocialLinks() {
    document.querySelectorAll('.social-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const social = link.dataset.social;
            const urls = {
                facebook: 'https://facebook.com/gelagelasorvetes',
                instagram: 'https://instagram.com/gelagelasorvetes',
                whatsapp: 'https://wa.me/5511999999999'
            };
            if (urls[social]) window.open(urls[social], '_blank');
        });

        link.addEventListener('mouseenter', () => {
            anime({
                targets: link,
                scale: 1.15,
                rotate: 10,
                duration: 400,
                easing: 'easeOutElastic(1, .6)'
            });
        });

        link.addEventListener('mouseleave', () => {
            anime({
                targets: link,
                scale: 1,
                rotate: 0,
                duration: 300,
                easing: 'easeOutQuad'
            });
        });
    });
}

// ==========================================
// CTA BUTTON
// ==========================================
function setupCTAButton() {
    const btn = document.querySelector('.btn-cta');
    if (btn) {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            anime({
                targets: btn,
                scale: 0.95,
                duration: 150,
                easing: 'easeInOutQuad',
                complete: () => {
                    anime({
                        targets: btn,
                        scale: 1,
                        duration: 300,
                        easing: 'easeOutElastic(1, .6)'
                    });
                }
            });
            playSound('click');
        });

        btn.addEventListener('mouseenter', () => {
            anime({
                targets: btn,
                translateY: -4,
                duration: 400,
                easing: 'easeOutElastic(1, .6)'
            });
        });

        btn.addEventListener('mouseleave', () => {
            anime({
                targets: btn,
                translateY: 0,
                duration: 300,
                easing: 'easeOutQuad'
            });
        });
    }
}

// ==========================================
// CONFETTI
// ==========================================
function createConfetti(x, y) {
    const colors = ['rgb(198, 116, 106)', 'rgb(17, 65, 123)', 'rgb(250, 206, 225)'];
    for (let i = 0; i < 15; i++) {
        const el = document.createElement('div');
        el.style.cssText = `
            position: fixed;
            left: ${x}px;
            top: ${y}px;
            width: 10px;
            height: 10px;
            background: ${colors[Math.floor(Math.random() * colors.length)]};
            border-radius: 50%;
            pointer-events: none;
            z-index: 1000;
        `;
        document.body.appendChild(el);

        anime({
            targets: el,
            translateX: (Math.random() - 0.5) * 200,
            translateY: Math.random() * 200 + 100,
            opacity: 0,
            scale: 0.5,
            duration: 1500,
            easing: 'easeOutQuad',
            complete: () => el.remove()
        });
    }
}

// ==========================================
// SOUND
// ==========================================
function playSound(type) {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        
        osc.connect(gain);
        gain.connect(ctx.destination);
        
        if (type === 'change') {
            osc.frequency.setValueAtTime(800, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(400, ctx.currentTime + 0.2);
            gain.gain.setValueAtTime(0.2, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0, ctx.currentTime + 0.2);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.2);
        } else if (type === 'click') {
            osc.frequency.setValueAtTime(600, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(200, ctx.currentTime + 0.1);
            gain.gain.setValueAtTime(0.1, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0, ctx.currentTime + 0.1);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.1);
        }
    } catch (e) {
        console.log('Som desabilitado');
    }
}
 