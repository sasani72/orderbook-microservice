## A simple orderbook API microservice to use in crypro exchange market using Laravel.

The algorithm used for storing and retrieving bids and asks in this microservice is based on Redis Sorted Sets. Redis Sorted Sets allow you to store prices along with their associated quantities. The scores(prices) are used to keep the elements(quantities) sorted.

Storing and retrieving orders with this algorithm has a time complexity of O(log(N)).

## Running Project

This project is built with Laravel Framework 10.

1. Clone the repository using git

    ```bash
    git clone https://github.com/sasani72/orderbook-microservice.git
    ```

2. `cd` into the folder created for cloned project:

    ```bash
    cd orderbook-microservice
    ```

3. Pull and build images using docker compose

    ```bash
    docker compose up -d
    ```

4. Run this script to initialize the laravel project

    ```bash
    docker compose exec app /usr/src/app/setup.sh
    ```

5. Create Kafka topics

    ```bash
    docker compose exec kafka bash
    kafka-topics.sh --create --bootstrap-server localhost:9092 --replication-factor 1 --partitions 1 --topic new-order
    kafka-topics.sh --create --bootstrap-server localhost:9092 --replication-factor 1 --partitions 1 --topic cancel-order
    exit
    ```

6. Running Kafka producer command to produce data streams

    ```bash
    docker compose exec app bash
    php artisan kafka:produce-order
    ```

7. Running Kafka consumer command to consume data streams

    ```bash
    php artisan kafka:consume-order
    ```

8. Start Laravel local server
    ```bash
     php artisan serve
    ```
9. You can now open another terminal to send requests for orderbooks:

    ```bash
      curl -X GET "http://localhost:8000/api/orderbook?symbol=ETHUSDT&depth=200"
    ```

## License

Licensed under the [MIT license](https://opensource.org/licenses/MIT).
