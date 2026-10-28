-- Tax rates

	-- get all tax rates

	CREATE PROCEDURE getAll(
		IN start INT,
		IN limit INT,
		IN cursor INT, -- current cursor (tax_rate_id) for cursor pagination
		OUT fetch_all, 
		OUT fetch_one
	)
	BEGIN
		-- tax_rate
		SELECT *
			FROM tax_rate WHERE 1 = 1
			
		
		@IF !empty(:limit) 
		THEN			
			-- cursor pagination
			@IF isset(:cursor)
			THEN
				AND tax_rate.tax_rate_id > :cursor
			END @IF
			LIMIT :limit OFFSET :start
		END @IF
		
		;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(tax_rate.tax_rate_id, tax_rate) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get tax_rate

	CREATE PROCEDURE get(
		IN tax_rate_id INT,
		OUT fetch_row 
	)
	BEGIN
		-- tax_rate
		SELECT *
			FROM tax_rate as _ WHERE tax_rate_id = :tax_rate_id;
	END
	
	-- add tax_rate

	CREATE PROCEDURE add(
		IN tax_rate ROW,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:tax_rate_data  = @FILTER(:tax_rate, tax_rate)
		
		
		INSERT INTO tax_rate 
			
			( @KEYS(:tax_rate_data) )
			
	  	VALUES ( :tax_rate_data);

	END
	
	-- edit tax_rate
	CREATE PROCEDURE edit(
		IN tax_rate ROW,
		IN tax_rate_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:tax_rate, tax_rate)

		UPDATE tax_rate
			
			SET @LIST(:tax_rate) 
			
		WHERE tax_rate_id = :tax_rate_id


	END
	
	-- delete tax_rate

	CREATE PROCEDURE delete(
		IN tax_rate_id ARRAY,
		OUT affected_rows,
		OUT affected_rows 
	)
	BEGIN
		-- tax_rate_to_user_group
		DELETE FROM tax_rate_to_user_group WHERE tax_rate_id IN (:tax_rate_id);

		-- tax_rate
		DELETE FROM tax_rate WHERE tax_rate_id IN (:tax_rate_id);
	END
