// DOMContentLoaded для инициализации скриптов после загрузки страницы
document.addEventListener('DOMContentLoaded', () => {
    initHeroButtonAnimation();
    initProductCardHover();
    initNewsSection();
    initProductSlider();
    initReviewFormValidation();
    initCartActions();
    initCheckoutForm();
});

function formatPrice(price) {
    return price.toFixed(2).replace('.', ',') + ' руб.';
}

// Анимация кнопки в секции Hero
function initHeroButtonAnimation() {
    const heroButton = document.querySelector('.hero .btn');
    heroButton.addEventListener('mouseover', () => {
        heroButton.style.backgroundColor = '#555';
    });
    heroButton.addEventListener('mouseout', () => {
        heroButton.style.backgroundColor = '#333';
    });
}

// Всплывающее сообщение при наведении на карточки товаров
function initProductCardHover() {
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach(card => {
        card.addEventListener('mouseover', () => {
            card.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.2)';
            card.style.transform = 'scale(1.05)';
        });
        card.addEventListener('mouseout', () => {
            card.style.boxShadow = 'none';
            card.style.transform = 'scale(1)';
        });
    });
}

// Динамическое обновление новостей
function initNewsSection() {
    const newsSection = document.querySelector('.news p');
    setTimeout(() => {
        newsSection.textContent = 'Подписывайтесь на наши соцсети для актуальных новостей!';
    }, 5000); // Обновление через 5 секунд
}

// Инициализация слайдера для секции "Популярные товары"
function initProductSlider() {
    const productGrid = document.querySelector('.product-grid');
    if (!productGrid) return;

    const products = Array.from(productGrid.children);
    let currentIndex = 0;

    // Создаем стрелки навигации
    const prevButton = document.createElement('button');
    prevButton.textContent = '‹';
    prevButton.classList.add('slider-prev');

    const nextButton = document.createElement('button');
    nextButton.textContent = '›';
    nextButton.classList.add('slider-next');

    productGrid.parentElement.appendChild(prevButton);
    productGrid.parentElement.appendChild(nextButton);

    // Переключение слайдов
    function updateSlider() {
        products.forEach((product, index) => {
            product.style.display = index >= currentIndex && index < currentIndex + 3 ? 'block' : 'none';
        });
    }

    // Обработчики событий для кнопок
    prevButton.addEventListener('click', () => {
        currentIndex = Math.max(currentIndex - 3, 0);
        updateSlider();
    });

    nextButton.addEventListener('click', () => {
        currentIndex = Math.min(currentIndex + 3, products.length - 3);
        updateSlider();
    });

    // Инициализация отображения
    updateSlider();
}

// Валидация формы отзыва
function initReviewFormValidation() {
    const form = document.querySelector('form[action="/api/submit_review.php"]');
    if (!form) return;

    form.addEventListener('submit', (event) => {
        const author = form.querySelector('#author').value.trim();
        const content = form.querySelector('#content').value.trim();

        if (!author || !content) {
            event.preventDefault();
            alert('Пожалуйста, заполните все поля формы.');
        }
    });
}

function initCartActions() {
    // Подтверждение удаления товара из корзины
    document.querySelectorAll('form[action="/api/remove_from_cart.php"]').forEach(form => {
        form.addEventListener('submit', (event) => {
            if (!confirm('Вы уверены, что хотите удалить этот товар из корзины?')) {
                event.preventDefault();
            }
        });
    });
}

function initCheckoutForm() {
    const form = document.querySelector('.checkout form');
    form.addEventListener('submit', (event) => {
        if (!confirm('Вы уверены, что хотите оформить заказ?')) {
            event.preventDefault();
        }
    });
}