-- Countries

	-- get all countries

	CREATE PROCEDURE getAll(
		IN status INT,
		IN search CHAR,
		IN start INT,
		IN limit INT,
		IN cursor INT, -- current cursor (country_id) for cursor pagination
		OUT fetch_all, 
		OUT fetch_one
	)
	BEGIN
		-- country
		SELECT *
			FROM country WHERE 1 = 1
			
		@IF isset(:status) AND :status != "" 
		THEN			
			AND status = :status
		END @IF		
		
		-- search
		@IF isset(:search) AND !empty(:search)
		THEN 
			AND country.name LIKE '%' || :search || '%' 
		END @IF		

		-- cursor pagination

		@IF isset(:cursor)

		THEN

		    AND country.country_id > :cursor

		END @IF

		ORDER BY status DESC, country_id

		@IF !empty(:limit) 
		THEN			
			LIMIT :limit OFFSET :start
		END @IF
		;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(country.country_id, country) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get country

	CREATE PROCEDURE get(
		IN country_id INT,
		OUT fetch_row 
	)
	BEGIN
		-- country
		SELECT *
			FROM country as _ WHERE country_id = :country_id;
	END
	
	-- add country

	CREATE PROCEDURE add(
		IN country ROW,
		OUT fetch_one
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:country_data  = @FILTER(:country, country)
		
		
		INSERT INTO country 
			
			( @KEYS(:country_data) )
			
	  	VALUES ( :country_data ) RETURNING country_id;

	END
	
	-- edit country
	
	CREATE PROCEDURE edit(
		IN country ROW,
		IN country_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:country, country)

		UPDATE country 
			
			SET @LIST(:country) 
			
		WHERE country_id = :country_id


	END
	
	-- delete country

	CREATE PROCEDURE delete(
		IN country_id ARRAY,
		OUT affected_rows 
	)
	BEGIN
		-- country
		DELETE FROM country WHERE country_id IN (:country_id);
	END
