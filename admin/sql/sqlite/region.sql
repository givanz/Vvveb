-- Region

	-- get all regions

	PROCEDURE getAll(
		IN country_id INT,
		IN status INT,
		IN search CHAR,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- region
		SELECT country.name as country,region.*
			FROM region AS region
		LEFT JOIN country ON country.country_id = region.country_id
		
		WHERE 1 = 1
		
		@IF !empty(:country_id) 
		THEN			
			AND region.country_id = :country_id
		END @IF				
		
		@IF !empty(:status) 
		THEN			
			AND region.status = :status
		END @IF		
	
		-- search
		@IF isset(:search) AND !empty(:search)
		THEN 
			AND region.name LIKE '%' || :search || '%' 
		END @IF	        
		
		
		ORDER BY region.status DESC, country.status DESC, region.region_id
		
		@IF !empty(:limit) 
		THEN			
			@SQL_LIMIT(:start, :limit)
		END @IF
		;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(region.region_id, region) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get region

	PROCEDURE get(
		IN region_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- region
		SELECT *
			FROM region as _ WHERE region_id = :region_id;
	END
	
	-- add region

	PROCEDURE add(
		IN region ARRAY,
		IN language_id INT,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:region_data  = @FILTER(:region, region);
		
		
		INSERT INTO region 
			
			( @KEYS(:region_data) )
			
	  	VALUES ( :region_data );

	END
	
	-- edit region

	CREATE PROCEDURE edit(
		IN region ARRAY,
		IN region_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:region, region);

		UPDATE region 
			
			SET @LIST(:region) 
			
		WHERE region_id = :region_id


	END
	
	-- delete region

	PROCEDURE delete(
		IN region_id ARRAY,
		OUT affected_rows, 
	)
	BEGIN
		-- region
		DELETE FROM region WHERE region_id IN (:region_id);
	END
