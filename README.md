# lightweight-php-orm ![code style](https://img.shields.io/badge/StyleCI-passed-green.svg)

Simple ORM based on pattern ActiveRecord.

How to use:
Firstly you need to init DataBase. Create class with name of your database (If the names of the database and the class do not match the case of letters, you can enter DB-name as field of class)

```php
// you need to extend AbstractDataBase class (HomeLibrary.php)
final class HomeLibrary extends AbstractDataBase
{

    public $dbtype = "mysql";           // driver for connection and executing queries
    public $dbname = "homelibrary";     // database name (dbname and this class are not equal by case of letters)
    public $user = "root";              // login
    public $password = "1111";          // password

}
```
Next, describe your tables:
```php
// you need to extend Table class -- base table class (Book.php)
class Book extends Table
{
    // fields of table in database
    public $id;
    public $book;

    public function __construct()
    {
        $this->table_name = "books";       // if name of table and this class are not equal, place name of table in this field
        // describe fields type
        $this->id = Field::primaryKey();   // describe PrimaryKey (with auto_increment)
        $this->book = Field::varchar(100); // describe varchar field of 100 symbools 
        $this->initTable();                // call method for initialisation table
    }

}

// the same for another table(s) (Author.php)
class Author extends Table
{

    public $id;
    public $author;

    public function __construct()
    {
        $this->table_name = "authors";

        $this->id = Field::primaryKey();
        $this->author = Field::varchar(100);
        $this->initTable();
    }

}
```
Also you can use entity relationships:
```php
class Library extends Table
{

    public $id;
    public $book;
    public $author;

    public function __construct()
    {
        $this->table_name = "library";

        $this->id = Field::primaryKey();
        // describe foreign key with cascade delete and update
        // You need to place class and field in it for foreign key
        $this->book = Field::foreignKey(Book::class, "id", [
            "on_delete" => "cascade", "on_update" => "cascade"
        ]);
        $this->author = Field::foreignKey(Author::class, "id", [
            "on_delete" => "cascade", "on_update" => "cascade"
        ]);
        $this->initTable();
    }

}
```
Now you can use ORM:

```php
// (index.php)

// now init your DataBase
$db = new HomeLibrary();

// then you can use ORM:

/* add new book */
$book1 = new Book();
$book1->book = "Book_56";
$book1->save();
/* add new author */
$author1 = new Author();
$author1->author = "Author1";
$author1->save();
/* add information about author and book in library */
$library = new Library();
$library->book = $book1;
$library->author = $author1;
$library->save();

/* find all information about book with id 9 in library */
$lib = Library::find(["book" => 6])[0];
print_r("ID " . $lib->id . "\n");
print_r("Book ID " . $lib->book->id . "\n");
print_r("Book " . $lib->book->book . "\n");
print_r("Author ID " . $lib->author->id . "\n");
print_r("Author " . $lib->author->author . "\n");

/* list of all books and aouthors from library */
$lib = Library::listAll();

foreach ($lib as $item) {
    print_r("ID " . $item->id . "\n");
    print_r("Book ID " . $item->book->id . "\n");
    print_r("Book " . $item->book->book . "\n");
    print_r("Author ID " . $item->author->id . "\n");
    print_r("Author " . $item->author->author . "\n");
}

/* remove book with name Book_56 from database */
Book::findFirst(["book" => "Book_56"])->remove();

```

# update v1.3:
Now it's possible to create tables in database from classes. All you need is describe classes and call migrate method:
```php
// migrate.php
$db = new HomeLibrary();
$book1 = new Book();
$book1->migrate();
$author1 = new Author();
$author1->migrate();
$library = new Library();
$library->migrate();
```
Be careful, when you call migrate for tables which are exists in database, their structure will be overwritten and all data will deleted.
