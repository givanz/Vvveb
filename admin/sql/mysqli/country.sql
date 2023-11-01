-- Countries

	-- get all countries

	PROCEDURE getAll(
		IN status INT,
		IN search CHAR,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- country
		SELECT *
			FROM country AS country WHERE 1 = 1
			
		@IF !empty(:status) 
		THEN			
			AND status = :status
		END @IF		

		-- search
		@IF isset(:search) AND !empty(:search)
		THEN 
			AND country.name LIKE CONCAT('%',:search,'%')
		END @IF	  

		ORDER BY status DESC, country_id

		@IF !empty(:limit) 
		THEN			
			@SQL_LIMIT(:start, :limit)
		END @IF
		;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(country.country_id, country) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get country

	PROCEDURE get(
		IN country_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- country
		SELECT *
			FROM country as _ WHERE country_id = :country_id;
	END
	
	-- add country

	PROCEDURE add(
		IN country ARRAY,
		IN language_id INT,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:country_data  = @FILTER(:country, country);
		
		
		INSERT INTO country 
			
			( @KEYS(:country_data) )
			
	  	VALUES ( :country_data );

	END
	
	-- edit country
	
	CREATE PROCEDURE edit(
		IN country ARRAY,
		IN country_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:country, country);

		UPDATE country 
			
			SET @LIST(:country) 
			
		WHERE country_id = :country_id


	END
	
	-- delete country

	PROCEDURE delete(
		IN country_id ARRAY,
		OUT affected_rows, 
	)
	BEGIN
		-- country
		DELETE FROM country WHERE country_id IN (:country_id);
	END
