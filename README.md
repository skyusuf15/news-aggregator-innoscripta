# 📰 News Aggregator API - Innoscripta Assessment

A backend system that aggregates, stores, and serves news articles from multiple public APIs.
Built with **Laravel 12**, this project demonstrates clean architecture, SOLID principles, and production-ready scheduling and API layers.

---

## 🚀 Features

* 🔄 **Automatic article aggregation** from multiple sources:

  * [NewsAPI](https://newsapi.org/)
  * [The Guardian API](https://open-platform.theguardian.com/)
  * [New York Times API](https://developer.nytimes.com/)
* 💾 **Persistent storage** using Eloquent ORM
* 🔍 **Filterable REST API**

  * Search by title, description, or content
  * Filter by category, source, author, and date
  * Support for multiple filters (sources, categories, authors)
  * User preferences for personalized article filtering
* 🕒 **Automated updates** via Laravel Commands with scheduling attributes
* 🧱 **SOLID design** with service and repository layers
* 🧪 **Comprehensive unit tests** with Mockery
* ✅ Built with Laravel 12 structure and best practices

---

## 🧩 Tech Stack

| Component    | Technology                                           |
| ------------ | ---------------------------------------------------- |
| Framework    | Laravel 12 (PHP 8.3+)                                |
| Database     | PostgreSQL or MySQL or Sqlite                                  |
| HTTP Client  | Laravel HTTP Client (Guzzle)                         |
| Scheduler    | Laravel Console Command with `#[Schedule]` attribute |
| Architecture | Service + Repository.                                |

---

## ⚙️ Installation

### 1️⃣ Clone the repository

```bash
git clone https://github.com/<your-username>/news-aggregator.git
cd news-aggregator
```

### 2️⃣ Install dependencies

```bash
composer install
```

### 3️⃣ Create environment file

```bash
cp .env.example .env
```

Update the following environment variables:

```env
DB_CONNECTION=mysql # or sqlite
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=news_aggregator
DB_USERNAME=root
DB_PASSWORD=

NEWS_API_KEY=your_newsapi_key
GUARDIAN_API_KEY=your_guardian_key
NYT_API_KEY=your_nyt_key
```

### 4️⃣ Generate application key

```bash
php artisan key:generate
```

### 5️⃣ Run migrations

```bash
php artisan migrate
```

---

## 🏗️ Project Structure Overview

```
app/
 ├── Console/
 │    └── Commands/
 │         └── FetchArticlesCommand.php   # Custom scheduled command for fetching articles
 │
 ├── Contracts/
 │    └── NewsProviderInterface.php      # Abstraction for all news services
 │
 ├── Http/
 │    ├── Controllers/
 │    │    └── Api/
 │    │         ├── ArticleController.php        # API endpoints for articles
 │    │         └── UserPreferenceController.php  # API endpoints for user preferences
 │    └── Resources/
 │         └── ArticleResource.php      # API resource for article transformation
 │
 ├── Models/
 │    ├── Article.php
 │    ├── Category.php
 │    ├── Source.php
 │    ├── User.php
 │    └── UserPreferredAuthor.php
 │
 ├── Providers/
 │    └── NewsServiceProvider.php       # Registers all source services
 │
 ├── Repositories/
 │    └── ArticleRepository.php         # Handles DB persistence and queries
 │
 └── Services/
      ├── ArticleService.php            # Business logic for filtering, sorting, preferences
      └── News/
           ├── NewsApiService.php
           ├── GuardianService.php
           ├── NyTimesService.php
           └── NewsFetcher.php          # Orchestrates fetching + saving
```

---

## ⚙️ How It Works

### 🧠 1. Data Fetching

Each source service implements the `NewsProviderInterface` and provides a `fetchArticles()` method that:

* Calls the external API.
* Normalizes article data into a common format.
* Returns an array of articles.

### 🧩 2. Aggregation & Persistence

`App\Services\News\NewsFetcher` orchestrates all registered providers.

```php
foreach ($this->providers as $provider) {
    $articles = $provider->fetchArticles();
    $this->articles->saveMany($articles);
}
```

Storage is delegated to `App\Repositories\ArticleRepository`, which handles:

* Source creation (if new)
* Category creation and assignment (if provided)
* Article upsert (updateOrCreate on URL)
* Query initialization with relationships
* Pagination

**Category Assignment:**
Articles are automatically assigned categories when fetched:
- NewsAPI: Uses `category` field
- The Guardian: Uses `sectionName` or `sectionId`
- New York Times: Uses `section` field

### 🔍 3. API Request Flow

When a request comes to `/api/articles`:

1. **ArticleController** receives the request
2. **ArticleRepository::initArticle()** initializes query with relationships
3. **ArticleService::applyUserPreferences()** applies user preferences if requested
4. **ArticleService::applyFilters()** applies search and filter criteria
5. **ArticleService::applySorting()** applies sorting
6. **ArticleRepository::paginate()** paginates results
7. **ArticleResource** transforms data for API response

---

### 🕒 4. Scheduling & Cron Command

The project uses a **custom Laravel Command** with the `#[Schedule]` attribute:

📄 `app/Console/Commands/FetchArticlesCommand.php`

```php
#[Schedule('hourly')]
class FetchArticlesCommand extends Command
{
    protected $signature = 'cron:fetch-articles';
    protected $description = 'Fetch and store articles from external news APIs';

    public function handle(NewsFetcher $fetcher)
    {
        $this->info('Fetching latest articles...');
        $fetcher->fetchAndStore();
        $this->info('Articles updated successfully.');
    }
}
```

* **Run manually:**

```bash
php artisan cron:fetch-articles
```

* **Automatic scheduling:**
  Add a cron entry to run Laravel’s scheduler every minute:

```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

Laravel automatically detects the `#[Schedule('hourly')]` attribute and executes the command hourly.

---

## 🌐 API Endpoints

### Articles

| Method | Endpoint                            | Description                   |
| ------ | ----------------------------------- | ----------------------------- |
| `GET`  | `/api/articles`                     | List all articles (paginated) |
| `GET`  | `/api/articles?search=keyword`      | Search articles               |
| `GET`  | `/api/articles?source=the-guardian` | Filter by source (single or array) |
| `GET`  | `/api/articles?category=business`   | Filter by category (single or array) |
| `GET`  | `/api/articles?author=John Doe`     | Filter by author (single or array) |
| `GET`  | `/api/articles?date=2025-11-08`     | Filter by publication date    |
| `GET`  | `/api/articles?use_preferences=true&user_id=1` | Filter by user preferences |
| `GET`  | `/api/articles/{id}`                | Retrieve a single article     |

**Query Parameters:**
- `search`: Search in title, description, or content
- `source`: Filter by source slug or name (supports single value or array: `source[]=guardian&source[]=nyt`)
- `category`: Filter by category slug or name (supports single value or array: `category[]=business&category[]=tech`)
- `author`: Filter by author name (supports single value or array: `author[]=John&author[]=Jane`)
- `date`: Filter by publication date (format: YYYY-MM-DD)
- `use_preferences`: Boolean flag to apply user preferences (requires `user_id` parameter)
- `user_id`: User ID for preference-based filtering (works without authentication)
- `limit`: Number of results per page (default: 10)

**Examples:**
```bash
# Single filter
GET /api/articles?author=John Doe
GET /api/articles?source=the-guardian
GET /api/articles?category=business

# Multiple filters
GET /api/articles?source[]=the-guardian&source[]=new-york-times
GET /api/articles?category[]=business&category[]=technology
GET /api/articles?author[]=John&author[]=Jane

# User preferences (no authentication required)
GET /api/articles?use_preferences=true&user_id=1
```

### Sources & Categories

| Method | Endpoint                            | Description                   |
| ------ | ----------------------------------- | ----------------------------- |
| `GET`  | `/api/sources`                      | List all sources              |
| `GET`  | `/api/categories`                   | List all categories           |

### User Preferences

All preference endpoints work without authentication by providing `user_id` parameter:

| Method | Endpoint                            | Description                   |
| ------ | ----------------------------------- | ----------------------------- |
| `GET`  | `/api/preferences?user_id=1`        | Get user preferences          |
| `POST` | `/api/preferences/sources`          | Update preferred sources      |
| `POST` | `/api/preferences/categories`       | Update preferred categories   |
| `POST` | `/api/preferences/authors`          | Update preferred authors      |
| `DELETE` | `/api/preferences?user_id=1`        | Clear all preferences         |

**Examples:**
```bash
# Get user preferences
GET /api/preferences?user_id=1

# Update preferred sources
POST /api/preferences/sources
{
  "user_id": 1,
  "source_ids": [1, 2, 3]
}

# Update preferred categories
POST /api/preferences/categories
{
  "user_id": 1,
  "category_ids": [1, 2]
}

# Update preferred authors
POST /api/preferences/authors
{
  "user_id": 1,
  "author_names": ["John Doe", "Jane Smith"]
}

# Use preferences when fetching articles
GET /api/articles?use_preferences=true&user_id=1
```

**Note:** All endpoints work without authentication by providing `user_id` parameter. For production, you may want to add authentication middleware.

---

## 🧪 Testing

Run all tests:

```bash
php artisan test
```

### Test Structure

The project includes comprehensive unit tests:

**Services Tests:**
- `NewsApiServiceTest` - Tests NewsAPI service
- `GuardianServiceTest` - Tests Guardian API service
- `NyTimesServiceTest` - Tests NY Times API service
- `NewsFetcherTest` - Tests article fetcher orchestrator

**Repository Tests:**
- `ArticleRepositoryTest` - Tests article repository with mocks

**Controller Tests:**
- `ArticleControllerTest` - Tests article API controller with mocks

**Testing Approach:**
- Uses Mockery for mocking dependencies
- Tests use mocks instead of factories
- Isolated unit tests for each component
- Tests cover success and error scenarios

**Example Mock Usage:**

```php
// Mock a service
$mockService = Mockery::mock(ArticleService::class);
$mockService->shouldReceive('applyFilters')
    ->once()
    ->andReturnUsing(function ($query) {
        return $query;
    });
```

---

## ⚡ Optional Enhancements

* Add Redis caching for repeated API queries
* Add authentication middleware (Laravel Sanctum) for production
* Swagger or Postman documentation for API
* Dockerize project for easier deployment
* Add feature tests for API endpoints
* Add article content caching

---

## 📜 License

This project is open-source and available under the [MIT License](LICENSE).

---

## 👨‍💻 Author

**Yusuf Sanusi**
Senior Backend Engineer | Laravel & TypeScript Enthusiast
[GitHub](https://github.com/skyusuf15)

