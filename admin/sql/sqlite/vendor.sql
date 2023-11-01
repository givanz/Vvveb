-- Vendors

	-- get one vendor

	CREATE PROCEDURE get(
		IN vendor_id INT,
		IN slug CHAR,
		OUT fetch_row, 
	)
	BEGIN
		-- vendor
		SELECT *
			FROM vendor as _ -- (underscore) _ means that data will be kept in main array
		WHERE 1 = 1 

		@IF isset(:vendor_id)
		THEN
			AND vendor_id = :vendor_id
		END @IF				
		
		@IF isset(:slug)
		THEN
			AND slug = :slug
		END @IF	

		LIMIT 1;	
		 -- SELECT *,vendor_option_id as _ 
			-- FROM vendor_option  WHERE vendor_id = :vendor_id;
			--@EACH(vendor_option, vendor_option_value) 
				-- SELECT *, vendor_option_value_id as _ FROM vendor_option_value pov 
					-- WHERE vendor_option_id = :vendor_option[vendor_option_id];

	END
	



	-- Edit vendor

	CREATE PROCEDURE edit(
		IN vendor ARRAY,
		IN vendor_id INT,
		OUT affected_rows
	)
	BEGIN

		-- SELECT * FROM vendor_option WHERE vendor_id = :vendor_id;

		-- allow only table fields and set defaults for missing values
		@FILTER(:vendor, vendor);
		
		UPDATE vendor 
			
			SET @LIST(:vendor) 
			
		WHERE vendor_id = :vendor_id
	END	



	-- Add new vendor

	CREATE PROCEDURE add(
		IN vendor ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:vendor  = @FILTER(:vendor, vendor);

		INSERT INTO vendor 
		
			( @KEYS(:vendor) )
			
		VALUES ( :vendor );
		
		INSERT INTO vendor_to_site 
		
			( vendor_id, site_id )
			
		VALUES ( @result.vendor, :site_id );
			

	END


	-- get all vendors 

	CREATE PROCEDURE getAll(

		-- variables
		IN  language_id INT,
		IN  user_group_id INT,
		IN  site_id INT,
		IN  search CHAR,
		
		-- pagination
		IN  start INT,
		IN  limit INT,
			
		-- return array of vendors for vendors query
		OUT fetch_all,
		-- return vendors count for count query
		OUT fetch_one,
	)
	BEGIN

		SELECT * FROM vendor AS vendor
		
			LEFT JOIN vendor_to_site p2s ON (vendor.vendor_id = p2s.vendor_id) 
			WHERE p2s.site_id = :site_id

			@IF isset(:search)
			THEN 
			
				AND name LIKE :search
				
			END @IF			

			
		@SQL_LIMIT(:start, :limit);
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(vendor.vendor_id, vendor) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;


	END


	-- delete vendor

	CREATE PROCEDURE delete(
		IN  vendor_id ARRAY,
		OUT affected_rows
	)
	BEGIN

		DELETE FROM vendor WHERE vendor_id IN (:vendor_id)
	 
	END
