-- Currencies

	-- get all currencies

	CREATE PROCEDURE getAll(
		IN status INT,
		IN cursor INT, -- current cursor (currency_id) for cursor pagination
		OUT fetch_row, 
		OUT fetch_one
	)
	BEGIN
		-- currency
		SELECT *, code as array_key
			FROM currency WHERE 1 = 1
			
		@IF isset(:status) AND :status != ""
		THEN			
			AND status = :status
		END @IF
		-- cursor pagination
		@IF isset(:cursor)
		THEN
			AND currency.currency_id > :cursor
		END @IF
		;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(currency.currency_id, currency) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get currency

	CREATE PROCEDURE get(
		IN currency_id INT,
		OUT fetch_row 
	)
	BEGIN
		-- currency
		SELECT *
			FROM currency as _ WHERE currency_id = :currency_id;
	END
	
	-- add currency

	CREATE PROCEDURE add(
		IN currency ROW,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:currency_data  = @FILTER(:currency, currency)
		
		
		INSERT INTO currency 
			
			( @KEYS(:currency_data) )
			
	  	VALUES ( :currency_data );

	END
	
	-- edit currency
	CREATE PROCEDURE edit(
		IN currency ROW,
		IN currency_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:currency, currency)

		UPDATE currency 
			
			SET @LIST(:currency) 
			
		WHERE currency_id = :currency_id


	END
	
	-- delete currency

	CREATE PROCEDURE delete(
		IN currency_id ARRAY,
		OUT affected_rows 
	)
	BEGIN
		-- currency
		DELETE FROM currency WHERE currency_id IN (:currency_id);
	END
