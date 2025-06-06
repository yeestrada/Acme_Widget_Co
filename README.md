# 🛒 Acme Widget Co - Shopping Cart System

This project is a proof of concept for Acme Widget Co's new sales system, developed in PHP with Laravel.

## Project Context

This project has been developed as a proof of concept to solve a specific business logic: calculating the total of a shopping cart considering products, delivery rules, and promotional offers.

Since this project is a proof of concept, the use of Laravel's config files for loading products, delivery rules, and offers was a deliberate choice. This approach offers several advantages at this stage:

✅ Simplifies development by removing the need to manage migrations, seeders, or database connections.

✅ Speeds up testing of different cart scenarios without needing to persist data.

✅ Keeps the logic focused on the business rules rather than infrastructure.

However, in a real-world production environment, storing products and offers in a database would be more appropriate for scalability. It would enable:

- Dynamic product and pricing management
- Admin interfaces for promotional campaigns
- Better integration with inventory, customers, and orders

This architectural decision keeps the project lightweight and testable while maintaining flexibility for future growth.

## Exercise Requirements

### Products
- Red Widget (R01): $32.95
- Green Widget (G01): $24.95
- Blue Widget (B01): $7.95

### Delivery Rules
- Orders < $50: $4.95
- Orders < $90: $2.95
- Orders >= $90: Free delivery

### Offers
- Buy one red widget, get the second half price

### Calculation Examples
| Products | Total |
|-----------|-------|
| B01, G01 | $37.85 |
| R01, R01 | $54.37 |
| R01, G01 | $60.85 |
| B01, B01, R01, R01, R01 | $98.27 |

## Implementation

### Basket Interface
The basket implements the following interface:
- Initialized with:
  - Product catalog
  - Delivery charge rules
  - Offers
- `add` method: Adds a product by its code
- `total` method: Calculates the total cost including delivery and offers

### Project Structure

#### Main Classes

##### `Product` (`app/Services/Basket/Product.php`)
- Represents a product in the system
- Properties: code, name, and price

##### `Basket` (`app/Services/Basket/Basket.php`)
- Implements the main basket logic
- Manages products, offers, and delivery
- Calculates the final total

##### `DeliveryRules` (`app/Services/Basket/DeliveryRules.php`)
- Implements delivery rules based on subtotal

##### `BuyOneGetOneHalfPrice` (`app/Services/Basket/Offers/BuyOneGetOneHalfPrice.php`)
- Implements the "second half price" offer
- Applies only to Red Widgets (R01)

### Configuration Files

#### `config/products.php`
```php
return [
    'R01' => [
        'name' => 'Red Widget',
        'price' => 32.95,
    ],
    'G01' => [
        'name' => 'Green Widget',
        'price' => 24.95,
    ],
    'B01' => [
        'name' => 'Blue Widget',
        'price' => 7.95,
    ],
];
```
Defines the available products with their codes, names, and prices.

#### `config/delivery.php`
```php
return [
    'rules' => [
        ['limit' => 50.0, 'cost' => 4.95],
        ['limit' => 90.0, 'cost' => 2.95],
        ['limit' => INF,  'cost' => 0.0],
    ],
];
```
Defines the delivery cost rules based on order subtotal.

#### `config/offers.php`
```php
return [
    'buy_one_get_half_price' => [
        'class' => BuyOneGetOneHalfPrice::class,
        'products' => ['R01'],
    ],
];
```
Configures the available offers and which products they apply to.

### Tests
The project includes unit tests that verify:
- Correct price calculations
- Offer applications
- Delivery rules
- Examples provided in the requirements

## Installation

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   ```
3. Start the server:
   ```bash
   php artisan serve
   ```

## Usage

1. Access the application at `http://localhost:8000`
2. Select products and quantities
3. Click "Calculate total"
4. View the total with delivery and discounts applied

## Assumptions and Design Decisions

1. **Rounding**: Prices are rounded to 2 decimal places
2. **Offers**: 
   - The "second half price" offer is applied in pairs
   - If there's an odd number of products, the last one is charged at full price
3. **Delivery**: 
   - Delivery cost is calculated on the subtotal after applying offers
4. **Interface**: 
   - A simple web interface has been added for testing purposes
   - Business logic is separated from the interface

## Technologies Used

- PHP 8.x
- Laravel Framework
- Tailwind CSS for the interface
- PHPUnit for testing
