-- Order transactions (money ledger)

	CREATE PROCEDURE getByOrder(
		IN order_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all,
		OUT fetch_one
	)
	BEGIN
		-- transaction
		SELECT *
			FROM order_transaction WHERE 1 = 1

		@IF isset(:order_id)
		THEN
			AND order_transaction.order_id = :order_id
		END @IF

		ORDER BY order_transaction.order_transaction_id ASC

		@IF isset(:limit)
		THEN
			LIMIT :limit OFFSET :start
		END @IF
		;

		SELECT count(*) FROM (

			@SQL_COUNT(order_transaction.order_transaction_id, order_transaction)

		) as count;
	END

	CREATE PROCEDURE add(
		IN transaction ROW,
		OUT insert_id
	)
	BEGIN

		:transaction_data = @FILTER(:transaction, order_transaction)

		INSERT INTO order_transaction

			( @KEYS(:transaction_data) )

	  	VALUES ( :transaction_data );

	END
