-- Vouchers

	-- get all vouchers

	CREATE PROCEDURE getAll(
		IN start INT,
		IN limit INT,
		IN cursor INT, -- current cursor (voucher_id) for cursor pagination
		OUT fetch_all, 
		OUT fetch_one
	)
	BEGIN
		-- voucher
		SELECT *
			FROM voucher WHERE 1 = 1
			
		
		-- cursor pagination
			
		
		@IF isset(:cursor)
			
		
		THEN
			
		
			AND voucher.voucher_id > :cursor
			
		
		END @IF
			
		
		LIMIT :limit OFFSET :start;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(voucher.voucher_id, voucher) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get voucher

	CREATE PROCEDURE get(
		IN voucher_id INT,
		OUT fetch_row 
	)
	BEGIN
		-- voucher
		SELECT *
			FROM voucher as _ WHERE voucher_id = :voucher_id;
	END
	
	-- add voucher

	CREATE PROCEDURE add(
		IN voucher ROW,
		OUT fetch_one
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:voucher_data  = @FILTER(:voucher, voucher)
		
		
		INSERT INTO voucher 
			
			( @KEYS(:voucher_data) )
			
	  	VALUES ( :voucher_data ) RETURNING vendor_id;

	END
	
	-- edit voucher
	CREATE PROCEDURE edit(
		IN voucher ROW,
		IN voucher_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:voucher, voucher)

		UPDATE voucher
			
			SET @LIST(:voucher) 
			
		WHERE voucher_id = :voucher_id


	END
	
	-- delete voucher

	CREATE PROCEDURE delete(
		IN voucher_id ARRAY,
		OUT affected_rows 
	)
	BEGIN
		-- voucher
		DELETE FROM voucher WHERE voucher_id IN(:voucher_id);
	END
	
