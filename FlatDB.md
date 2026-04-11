# 📘 README.md  
### FlatDB CMS — A Lightweight NoSQL Flat‑File Database + CMS Toolkit for PHP

FlatDB CMS is a lightweight, schema‑less, file‑based NoSQL database written in pure PHP.  
It includes optional CMS features such as:

- Auto‑slug generation  
- Soft deletes  
- Revision history  
- Simple indexing  
- Relationships (joins)  
- Tiny template engine  
- REST API scaffolding  
- Admin panel scaffolding (starter)

Perfect for small CMS projects, micro‑sites, embedded apps, or environments where you want **zero dependencies** and **no SQL server**.

---

# 🚀 Features

### **Database Core**
- JSON‑based flat‑file storage  
- Collections (like MongoDB)  
- Documents with auto‑generated `_id`  
- Query engine with operators:  
  - `$eq`, `$ne`, `$gt`, `$lt`, `$in`, `$regex`  
  - `$and`, `$or`, `$not`  
- Nested field querying (`author.name`)  

### **CMS Enhancements**
- Auto‑slug generation  
- Soft deletes (`deleted_at`)  
- Revision history  
- Simple indexing for faster lookups  
- Relationship helpers (joins in PHP)  

### **Framework Utilities**
- Tiny template engine  
- REST API scaffolding  
- Admin panel scaffolding (starter)

---

# 📁 Project Structure

```
/data
    pages.json
    users.json
    index_pages_slug.json

/src
    FlatDB.php
    PageRepository.php
    cms_helpers.php
    View.php

/public
    api.php
    index.php

/views
    page.php
```

---

# 📦 Installation

Just drop the files into your project:

```
/src/FlatDB.php
/src/PageRepository.php
/src/cms_helpers.php
/src/View.php
```

Make sure your `/data` directory is writable.

---

# 🧠 Core: FlatDB (NoSQL Flat‑File Database)

### **Insert**

```php
$user = $db->insert('users', [
    'name' => 'Thaddeus',
    'email' => 'thaddeus@example.com'
]);
```

### **Find**

```php
$admins = $db->find('users', ['role' => 'admin']);
```

### **Query Operators**

```php
$recent = $db->find('pages', [
    'created_at' => ['$gt' => time() - 86400]
]);
```

### **Regex Search**

```php
$matches = $db->find('pages', [
    'title' => ['$regex' => '/about/i']
]);
```

### **Nested Fields**

```php
$pages = $db->find('pages', [
    'author.name' => 'Thaddeus'
]);
```

---

# 📝 CMS Helpers

## Auto‑Slug Generation

```php
$slug = slugify("About Our Company");
// about-our-company
```

Automatically applied when creating or updating pages.

---

# 🗂 PageRepository (CMS Logic Layer)

Handles:

- Slugs  
- Soft deletes  
- Revision history  
- Relationships  
- Filtering out deleted pages  

### **Create Page**

```php
$page = $pages->create([
    'title' => 'About Us',
    'body' => 'Welcome...',
    'status' => 'published',
    'author_id' => 'u123'
]);
```

### **Update Page (with revision)**

```php
$updated = $pages->update($page['_id'], [
    'title' => 'About Our Company'
]);
```

### **Soft Delete**

```php
$pages->softDelete($page['_id']);
```

### **Restore**

```php
$pages->restore($page['_id']);
```

### **Get Revisions**

```php
$revisions = $pages->revisions($page['_id']);
```

---

# 🔗 Relationships (Joins)

FlatDB does not join internally — you join in PHP (like MongoDB).

## 1. Manual Join

```php
$page = $db->findOne('pages', ['_id' => 'p1']);
$author = $db->findOne('users', ['_id' => $page['author_id']]);

$page['_author'] = $author;
```

## 2. Batch Join (Fastest)

```php
$pages = $db->find('pages', ['status' => 'published']);

$authorIds = array_unique(array_column($pages, 'author_id'));

$authors = $db->find('users', [
    '_id' => ['$in' => $authorIds]
]);

$map = [];
foreach ($authors as $a) $map[$a['_id']] = $a;

foreach ($pages as &$p) {
    $p['_author'] = $map[$p['author_id']] ?? null;
}
```

## 3. Relationship Helper

```php
$page = $pages->withAuthor($page);
```

---

# ⚡ Simple Indexing

Indexes are stored as:

```
data/index_pages_slug.json
```

Rebuild index:

```php
$db->reindex('pages', 'slug');
```

---

# 🎨 Tiny Template Engine

### Template (`views/page.php`)

```php
<h1><?= htmlspecialchars($page['title']) ?></h1>
<article><?= $page['body'] ?></article>
```

### Render

```php
$view = new View(__DIR__ . '/views');
echo $view->render('page', ['page' => $page]);
```

---

# 🌐 REST API Scaffolding

`public/api.php`:

### GET all pages

```
GET /api.php?path=pages
```

### GET one page

```
GET /api.php?path=pages/{id}
```

### POST create page

```
POST /api.php?path=pages
```

### PUT update page

```
PUT /api.php?path=pages/{id}
```

### DELETE soft delete

```
DELETE /api.php?path=pages/{id}
```

---

# 🛠 Admin Panel Scaffolding (Starter)

Suggested routes:

```
/admin/login.php
/admin/pages/index.php
/admin/pages/edit.php?id=...
/admin/pages/revisions.php?id=...
```

Use:

- `PageRepository` for CRUD  
- `View` for rendering  
- Sessions for auth  

---

# 🧩 Future Enhancements

- Full text search  
- Caching layer  
- Media library  
- Role-based permissions  
- Static site generator mode  

---

# ❤️ Credits

Built for developers who want a **simple, dependency‑free, Mongo‑like CMS** in pure PHP.