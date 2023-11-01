-- Languages

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
	
	-- get data

	PROCEDURE getData(
		IN site_id INT,
		IN country_id INT,
		IN language_id INT,
		OUT fetch_all, 		
		OUT fetch_all, 		
		OUT fetch_all, 		
		OUT fetch_all, 		
		OUT fetch_all, 		
	)
	BEGIN
		-- country
		SELECT 
			name,  country_id as array_key, 
			name as array_value
			
		FROM country as country_id WHERE status = 1;
		
		-- Region
		SELECT 
			name,  region_id as array_key, 
			name as array_value
			
		FROM region as region_id WHERE country_id = :country_id AND status = 1;		
		
		-- language
		SELECT 
			name,  language_id as array_key, 
			name as array_value
			
		FROM language as language_id WHERE status = 1;		
		
		-- currency
		SELECT 
			name,  currency_id as array_key, 
			name as array_value
			
		FROM currency as currency_id WHERE status = 1;
		
		-- order_status
		SELECT 
			name,  order_status_id as array_key, 
			name as array_value
			
		FROM order_status as order_status_id WHERE language_id = :language_id;
				
		-- weight_type
		SELECT 
		
			*, weight_type_id.weight_type_id as array_key,
			weight_desc.name as array_value -- only set name as value and return 
			
		FROM weight_type as weight_type_id
			LEFT JOIN weight_type_content as weight_desc
				ON weight_type_id.weight_type_id = weight_desc.weight_type_id; -- (underscore) _ means that data will be kept in main array
					
		-- length_type
		SELECT 
		
			*, length_type_id.length_type_id as array_key,
			length_desc.name as array_value -- only set name as value and return 
			
		FROM length_type as length_type_id
			LEFT JOIN length_type_content as length_desc
				ON length_type_id.length_type_id = length_desc.length_type_id; -- (underscore) _ means that data will be kept in main array				
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
	
	-- add site

	PROCEDURE add(
		IN site ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:site_data  = @FILTER(:site, site);
		
		
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
	
