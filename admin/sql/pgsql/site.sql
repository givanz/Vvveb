-- Sites

	-- get all sites

	PROCEDURE getAll(
		IN start INT,
		IN limit INT,
		IN site_id ARRAY,
		
		-- return array of sites for sites query
		OUT fetch_all,
		-- return sites count for count query
		OUT fetch_one,
	)
	BEGIN
		-- site
		SELECT *, site_id as array_key
			FROM site

		WHERE 1 = 1
		
		-- site_id
		@IF isset(:site_id) && !empty(:site_id)
		THEN
			AND site.site_id IN (:site_id)
		END @IF

		-- limit
		@IF isset(:limit)
		THEN
			@SQL_LIMIT(:start, :limit)
		END @IF;	
		
		-- SELECT FOUND_ROWS() as count;
		SELECT count(*) FROM (
			
			@SQL_COUNT(site.site_id, site) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
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
		OUT fetch_all, 		
		OUT fetch_all 		
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
	
	-- site data

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
		OUT fetch_one
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:site_data  = @FILTER(:site, site)
		
		
		INSERT INTO site 
			
			( @KEYS(:site_data) )
			
	  	VALUES ( :site_data ) RETURNING site_id;

	END
	
	-- edit site
	PROCEDURE edit(
		IN site ARRAY,
		IN site_id INT,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:site_data  = @FILTER(:site, site)
	
		UPDATE site 
			
			SET @LIST(:site_data) 
			
		WHERE site_id = :site_id


	END
	
	-- delete site

	CREATE PROCEDURE delete(
		IN  site_id ARRAY,
		OUT affected_rows
		OUT affected_rows
		OUT affected_rows
		OUT affected_rows
		OUT affected_rows
		OUT affected_rows
		OUT affected_rows
	)
	BEGIN
		
		DELETE FROM post_to_site WHERE site_id IN (:site_id);
		DELETE FROM product_to_site WHERE site_id IN (:site_id);
		DELETE FROM menu_to_site WHERE site_id IN (:site_id);
		DELETE FROM taxonomy_to_site WHERE site_id IN (:site_id);
		DELETE FROM manufacturer_to_site WHERE site_id IN (:site_id);
		DELETE FROM vendor_to_site WHERE site_id IN (:site_id);
		DELETE FROM site WHERE site_id IN (:site_id);
	 
	END
