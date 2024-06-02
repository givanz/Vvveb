-- Sites

	-- get all sites

	PROCEDURE getAll(
		IN start INT,
		IN limit INT,
		
		-- return array of sites for sites query
		OUT fetch_all,
		-- return sites count for count query
		OUT fetch_one,
	)
	BEGIN
		-- site
		SELECT *, site_id as array_key
			FROM site as sites

		-- limit
		@IF isset(:limit)
		THEN
			@SQL_LIMIT(:start, :limit)
		END @IF;	
		
		-- SELECT FOUND_ROWS() as count;
		SELECT count(*) FROM (
			
			@SQL_COUNT(sites.site_id, site) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;
	END	
	
	-- get site

	PROCEDURE get(
		IN site_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- site
		SELECT *
			FROM site as _ WHERE site_id = :site_id;
	END

	-- get site data

	PROCEDURE getSiteData(
		IN site ARRAY,
		OUT fetch_one, 		
		OUT fetch_one, 		
		OUT fetch_one, 		
		OUT fetch_one, 		
		OUT fetch_one, 		
		OUT fetch_one
	)
	BEGIN
		-- country
		SELECT 
			name
		FROM country as country_id WHERE country_id = :site.country_id;
		
		-- Region
		SELECT 
			name			
		FROM region as region_id WHERE region_id = :site.region_id;		
		
		-- language
		SELECT 
			name			
		FROM language as language_id WHERE language_id = :site.language_id;			
		
		-- currency
		SELECT 
			name		
		FROM currency as currency_id WHERE currency_id = :site.currency_id;	
				
		-- weight_type
		SELECT 
			unit			
		FROM weight_type_content as weight_type WHERE weight_type_id = :site.weight_type_id;		
		
		-- length_type
		SELECT 
			unit			
		FROM length_type_content AS length_type WHERE length_type_id = :site.length_type_id;
				
	END	

	-- add site

	PROCEDURE add(
		IN site ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:site_data  = @FILTER(:site, site)
		
		
		INSERT INTO site 
			
			( @KEYS(:site_data) )
			
	  	VALUES ( :site_data );

	END
	
	-- edit site
	PROCEDURE edit(
		IN site ARRAY,
		IN site_id INT,
		OUT insert_id
	)
	BEGIN
		
	
		UPDATE site 
			
			SET @LIST(:site) 
			
		WHERE site_id = :site_id


	END
	
