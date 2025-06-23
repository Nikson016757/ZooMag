<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php');
?>
<main>
    <section class="contacts">
        <h1>Контакты</h1>
        <div class="contact-container">
            <div class="contact-info">
                <h2>Наши магазины</h2>
                
                <div class="shop-location">
                    <h3>Центральный магазин</h3>
                    <p><i class="fas fa-map-marker-alt"></i> Москва, ул. Пушкинская, д. 10</p>
                    <p><i class="fas fa-phone"></i> +7 (495) 123-45-67</p>
                    <p><i class="fas fa-clock"></i> Ежедневно с 9:00 до 21:00</p>
                    <div class="map-container" id="map-central"></div>
                </div>
                
                <div class="shop-location">
                    <h3>Филиал на севере</h3>
                    <p><i class="fas fa-map-marker-alt"></i> Москва, ул. Ленинградская, д. 25</p>
                    <p><i class="fas fa-phone"></i> +7 (495) 987-65-43</p>
                    <p><i class="fas fa-clock"></i> Ежедневно с 10:00 до 20:00</p>
                    <div class="map-container" id="map-north"></div>
                </div>
            </div>
            
            <div class="contact-form">
                <h2>Форма обратной связи</h2>
                <form method="POST" action="/api/submit_contact.php">
                    <div class="form-group">
                        <label for="name">Ваше имя:</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Ваш email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Тема:</label>
                        <input type="text" id="subject" name="subject" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Сообщение:</label>
                        <textarea id="message" name="message" rows="5" required></textarea>
                    </div>
                    
                    <button type="submit" class="btn">Отправить</button>
                </form>
            </div>
        </div>
    </section>
</main>

<script src="https://api-maps.yandex.ru/2.1/?apikey=ваш_api_ключ&lang=ru_RU" type="text/javascript"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация карты для центрального магазина
    ymaps.ready(function() {
        var centralMap = new ymaps.Map("map-central", {
            center: [55.76, 37.64], // Координаты центрального магазина
            zoom: 15
        });
        
        var centralPlacemark = new ymaps.Placemark([55.76, 37.64], {
            hintContent: 'Наш центральный магазин',
            balloonContent: 'Зоомагазин на Пушкинской'
        });
        
        centralMap.geoObjects.add(centralPlacemark);
        
        // Инициализация карты для северного филиала
        var northMap = new ymaps.Map("map-north", {
            center: [55.85, 37.53], // Координаты северного филиала
            zoom: 15
        });
        
        var northPlacemark = new ymaps.Placemark([55.85, 37.53], {
            hintContent: 'Наш филиал на севере',
            balloonContent: 'Зоомагазин на Ленинградской'
        });
        
        northMap.geoObjects.add(northPlacemark);
    });
});
</script>

<?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'); ?>