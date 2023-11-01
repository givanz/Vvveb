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
	
