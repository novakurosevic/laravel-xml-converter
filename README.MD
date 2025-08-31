# Laravel XML Converter Package

## 🧾 Introduction

Since I started working with Laravel, I always wanted a simple helper that could convert XML files to JSON.

I created the first version of this package to do exactly that. While the package isn't perfect, it can handle a wide range of XML inputs. If you find any issues, feel free to report them.

I should note that this package was created with the help of AI. In my case, I used [ChatGPT](https://github.com/openai), and it saved me many hours of coding.

## ❓ What does this package do?

This package provides a flexible and powerful way to convert XML strings to JSON or PHP arrays.
It supports:

- DTD and XSD validation
- Namespace tagging
- CDATA handling

---

## 🚀 Installation

### Requirements

- PHP >= 8.1
- Laravel >= 9
- PHP Extensions:
  - `simplexml`
  - `libxml`
  - `dom`

### Composer Installation

```bash
composer require noki/laravel-xml-converter
```



## ⚙️ PHP Extensions Setup

### Linux (Debian/Ubuntu)

```bash
sudo apt-get update
sudo apt-get install php-simplexml php-xml php-dom
```

> Example for PHP 8.3 — be sure to use the correct package names:

First, check your PHP version:

```bash
php -v
```

Then install extensions for your version:

```bash
sudo apt-get install php8.3-simplexml php8.3-xml php8.3-dom
```

---

### macOS (with Homebrew)

```bash
brew install php
# OR, if PHP is already installed:
brew reinstall php
```

> Required extensions (`simplexml`, `libxml`, `dom`) are bundled with PHP on macOS.

---

### Windows

1. Open your `php.ini` file.
2. Ensure the following lines are **uncommented** (remove the `;` at the beginning):

```ini
extension=simplexml
extension=dom
extension=libxml
```

3. Restart your web server (Apache, Nginx) or PHP-FPM.

---

## 📦 Usage

### Namespace

```php
use Noki\XmlConverter\Convert;
```

---

### 1. Convert XML to JSON

```php
$json = Convert::xmlToJson($xmlString);
```

---

### 2. Convert XML to array

```php
$array = Convert::xmlToArray($xmlString);
```

---

### 3. Full control with options

```php
$array = Convert::xmlToArray(
    $xmlString,
    namespace_in_tag_name: true,
    is_cdata: true,
    schema_path: '/path/to/schema.xsd' // or '' to enable DTD validation
);
```

- `namespace_in_tag_name`: If true, keys will include namespace prefixes (e.g., `ns:tag`).
- `is_cdata`: If true, CDATA sections are preserved as-is.
- `schema_path`:
    - Path to an `.xsd` file for XSD validation.
    - Use `''` (empty string) to enable DTD validation (your XML must contain `<!DOCTYPE ...>`).
    - Use `null` to skip validation.

---

### 4. Convert XML to JSON with options

```php
$json = Convert::xmlToJson(
    $xmlString,
    namespace_in_tag_name: true,
    is_cdata: false,
    schema_path: null
);
```

---

## 🧪 Example

### Sample XML with namespaces and CDATA

```php
$xml = <<<XML
<book xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="book.xsd">
    <title><![CDATA[The Great Gatsby]]></title>
    <author>F. Scott Fitzgerald</author>
    <year>1925</year>
</book>
XML;

$json = Convert::xmlToJson($xml, true, true, '/path/to/book.xsd');
```

---

## 🛠 Error Handling

- If the XML is invalid, an `InvalidArgumentException` will be thrown.
- If schema validation fails, a descriptive exception is thrown (with optional logging).
- CDATA sections and empty tags are handled gracefully.

---

## 🧩 Integration with Laravel

You can use this package in controllers, services, or jobs:

```php
use Noki\XmlConverter\Convert;

$data = Convert::xmlToArray($request->getContent(), true);
```

---

## 📄 License

MIT License.
See [LICENSE.md](LICENSE.md) for details.

---

## 🙌 Contributing

Feel free to fork and submit a PR!
Bug reports and feature requests are always welcome.

---

## 🔗 Author

- [Novak Urosevic](https://github.com/novakurosevic)
- [ChatGPT](https://github.com/openai)

---

## 📝 Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for recent updates.

