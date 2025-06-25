Сервіс для моніторингу оголошень на OLX з автоматичними сповіщеннями в Telegram.

## Функціональність

- ✅ Моніторинг оголошень OLX через GraphQL API
- 🚫 Налаштовувані фільтри пошуку (категорія, ціна, місце розташування)
- ✅ Автоматичні сповіщення в Telegram про:
    - Нові оголошення
    - Зміни цін
    - Видалені оголошення
- ✅ Веб-інтерфейс для перегляду статистики
- ✅ Консольні команди для управління
- ✅ Підтримка Docker та systemd

## Вимоги

- PHP 8.4+
- MySQL 8.0+
- Composer
- Telegram Bot Token

## Встановлення

### 1. Клонування та налаштування

```bash
git clone <repository>
cd olx-monitor
composer install
cp .env.example .env
```

### 2. Налаштування .env файлу

```bash
DATABASE_USER=olx_user
DATABASE_PASSWORD=olx_password
DATABASE_NAME=olx_monitor
DATABASE_HOST=mysql
TELEGRAM_BOT_TOKEN=
OLX_API_URL=https://www.olx.ua/apigateway/graphql
LOG_LEVEL=info
MONITOR_INTERVAL=3600
```

### 3. Ініціалізація бази даних

```bash
make setup
```

## Використання

### Створення фільтра

```bash
make create-filter \
  NAME="Квартири в центрі" \
  CATEGORY="нерухомість" \
  SUBCATEGORY="квартири" \
  TYPE="довгострокова оренда" \
  PRICE="10000" OR "10000,15000" \
  REGION_ID="10" \
  CITY_ID="247" \
  APARTMENT_TYPE="житловий фонд 2001-2010" \
  AREA="50" OR "50,75" \
  DISTANCE="10" (e.g. km)
```

### Запуск моніторингу

#### Одноразовий запуск
```bash
make monitor
```

#### Daemon режим
```bash
make daemon
```

#### Через systemd
```bash
make install-service
make status
make service-logs
```

#### Через cron
```bash
make install-cron
```

### Docker

```bash
make docker-build
make docker-up
make docker-logs
```

## API команди

### Консольні команди

```bash
# Створити фільтр
php bin/console filter:create --name="Test" --category="нерухомість" --subcategory="квартири" --type="довгострокова оренда"

# Переглянути фільтри
php bin/console filter:list

# Запустити моніторинг
php bin/console monitor:run

# Моніторинг конкретного фільтра
php bin/console monitor:run --filter-id="uuid"
```

### Веб-інтерфейс

Відкрийте браузер на `http://localhost:8080` для доступу до веб-інтерфейсу.

## Структура проекту

```
src/
├── Entity/           # Сутності
├── Repository/       # Репозиторії для роботи з БД
├── Service/          # Бізнес-логіка
├── Http/            # HTTP клієнти
├── Console/         # Консольні команди
└── DependencyInjection/ # DI контейнер

bin/
├── console          # Точка входу для команд
└── monitor-daemon   # Daemon для постійного моніторингу

public/
└── index.php        # Веб-інтерфейс

migrations/          # SQL міграції
docker/             # Docker конфігурації
systemd/            # Systemd сервіси
cron/              # Cron завдання
```

## Конфігурація OLX API

Сервіс використовує GraphQL API OLX. Основні параметри:

### Категорії (приклади)
- `нерухомість` → `квартири` → `довгострокова оренда`
- `нерухомість` → `будинки` → `продаж`
- `транспорт` → `легкові автомобілі`

### Фільтри
- `price` - діапазон цін (наприклад, 10000 або 10000,15000)
- `region-id` - ID регіону
- `city-id` - ID міста
- `apartment-type` - тип квартири
- `area` - площа (наприклад 10 або 10,20)
- `distance` - відстань у км (наприклад 10)

## Логування

Логи зберігаються в:
- `logs/app.log` - основні логи
- stdout - для Docker
- systemd journal - для systemd сервісу

## Моніторинг та алерти

### Telegram сповіщення

🆕 **Нове оголошення**
```
🆕 Нове оголошення!

📋 2-кімнатна квартира в центрі
💰 15000 UAH
📍 Київ, Шевченківський район
🔗 https://olx.ua/...
```

📈 **Зміна ціни**
```
📈 Зміна ціни!

📋 2-кімнатна квартира в центрі
💰 Було: 15000 UAH
💰 Стало: 14000 UAH
📊 Різниця: -1000 UAH
🔗 https://olx.ua/...
```

❌ **Видалене оголошення**
```
❌ Оголошення видалено

📋 2-кімнатна квартира в центрі
💰 15000 UAH
📍 Київ, Шевченківський район
```

## Тестування

```bash
make test
```

## Розробка

### Кодстайл
```bash
make fix  # Виправити стиль коду
```

### Статичний аналіз
```bash
./vendor/bin/phpstan analyse src --level=8
```
