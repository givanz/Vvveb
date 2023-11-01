-- Currencies

	-- get all stock status

	PROCEDURE getAll(
		IN language_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- stock_status
		SELECT *
			FROM stock_status AS stock_status WHERE 1 = 1
			
		@IF !empty(:language_id) 
		THEN			
			AND language_id = :language_id
		END @IF
		
		@SQL_LIMIT(:start, :limit);
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(stock_status.stock_status_id, stock_status) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get stock_status

	PROCEDURE get(
		IN stock_status_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- stock_status
		SELECT *
			FROM stock_status as _ WHERE stock_status_id = :stock_status_id;
	END
	
	-- add stock_status

	PROCEDURE add(
		IN stock_status ARRAY,
		IN language_id INT,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:stock_status_data  = @FILTER(:stock_status, stock_status);
		
		
		INSERT INTO stock_status 
			
			( @KEYS(:stock_status_data), language_id )
			
	  	VALUES ( :stock_status_data, :language_id );

	END
	
	-- edit stock_status
	CREATE PROCEDURE edit(
		IN stock_status ARRAY,
		IN stock_status_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:stock_status, stock_status);

		UPDATE stock_status 
			
			SET @LIST(:stock_status) 
			
		WHERE stock_status_id = :stock_status_id


	END
	
