# MySQL Storage

Adds support for MySQL storage in your application.

<!-- markdownlint-disable MD033-->
<!-- markdownlint-disable MD041-->
<p align="center">
    <img src="./phpolar.svg" alt="Application Logo" width="120px" height="120px" />
</p>

<p align="center">
    <img src="https://coveralls.io/repos/github/phpolar/mysql-storage/badge.svg?branch=main" alt="Coverage Status Badge">
    <img src="https://poser.pugx.org/phpolar/mysql-storage/v" alt="Latest Stable Version">
    <img src="https://poser.pugx.org/phpolar/mysql-storage/downloads" alt="Total Downloads">
    <img src="https://poser.pugx.org/phpolar/mysql-storage/license" alt="License">
    <img src="https://poser.pugx.org/phpolar/mysql-storage/require/php" alt="PHP Version Require">
    <img src="https://github.com/phpolar/mysql-storage/actions/workflows/weekly.yml/badge.svg" alt="Weekly Check">
</p>

<p align="center">
    <a href="https://phpolar.org/">Website</a>
</p>

## Quick start

```bash
composer require phpolar/mysql-storage
```

## Objectives

1. Keep project small. See [thresholds](#thresholds)
1. Automatically load and persist data

**Note** For more details see the [acceptance tests results](./acceptance-test-results.md)

### Example 1

```php
$mysqlStorage = new MySqlStorage(
    connection: $connection,
    tableName: "table_name",
    typeClassName: Person::class,
);

$mysqlStorage->save($item1->id, $item);
$mysqlStorage->replace($updatedItem->id, $updatedItem);
$mysqlStorage->remove($item2->id);

$item3 = $mysqlStorage->find("id3")
    ->orElse(static fn() => new NotFound())
    ->tryUnwrap();

$allItems = $mysqlStorage->findAll();
```

## Example Class for Items in Storage

```php
use Phpolar\Phpolar\AbstractModel;

class Person extends AbstractModel
{
    #[PrimaryKey]
    #[Hidden]
    public string $id;

    public string $firstName;

    public string $lastName;

    public string $address1;

    public string $address2;

    public function getPrimaryKey(): string
    {
        return $id;
    }
}
```

## Thresholds

|      Module    |Source Code Size * |Memory Usage|  Required |
|----------------|-------------------|------------|-----------|
|     phpolar/mysql-storage    |       9 kB       |   150 kB   |      x    |

* Note: Does not include comments.
