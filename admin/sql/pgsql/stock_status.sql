-- Currencies

	-- get all stock status

	CREATE PROCEDURE getAll(
		IN language_id INT,
		IN start INT,
		IN limit INT,
		IN cursor INT, -- current cursor (stock_status_id) for cursor pagination
		OUT fetch_all, 
		OUT fetch_one
	)
	BEGIN
		-- stock_status
		SELECT *
			FROM stock_status WHERE 1 = 1
			
		@IF !empty(:language_id) 
		THEN			
			AND language_id = :language_id
		END @IF
		
		-- cursor pagination
		
		@IF isset(:cursor)
		
		THEN
		
			AND stock_status.stock_status_id > :cursor
		
		END @IF
		
		LIMIT :limit OFFSET :start;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(stock_status.stock_status_id, stock_status) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get stock_status

	CREATE PROCEDURE get(
		IN stock_status_id INT,
		OUT fetch_row 
	)
	BEGIN
		-- stock_status
		SELECT *
			FROM stock_status as _ WHERE stock_status_id = :stock_status_id;
	END
	
	-- add stock_status

	CREATE PROCEDURE add(
		IN stock_status ROW,
		IN language_id INT,
		OUT fetch_one
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:stock_status_data  = @FILTER(:stock_status, stock_status)
		
		
		INSERT INTO stock_status 
			
			( @KEYS(:stock_status_data), language_id )
			
	  	VALUES ( :stock_status_data, :language_id ) RETURNING role_id;

	END
	
	-- edit stock_status
	CREATE PROCEDURE edit(
		IN stock_status ROW,
		IN stock_status_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:stock_status, stock_status)

		UPDATE stock_status 
			
			SET @LIST(:stock_status) 
			
		WHERE stock_status_id = :stock_status_id


	END
	
	-- delete stock_status

	CREATE PROCEDURE delete(
		IN stock_status_id ARRAY,
		OUT affected_rows 
	)
	BEGIN
		-- user_group
		DELETE FROM stock_status WHERE stock_status_id IN (:stock_status_id);
	END
