-- Region group

	-- get all return region groups

	CREATE PROCEDURE getAll(
		IN start INT,
		IN limit INT,
		IN cursor INT, -- current cursor (region_group_id) for cursor pagination
		OUT fetch_all, 
		OUT fetch_one
	)
	BEGIN
		-- region_group
		SELECT *
			FROM region_group WHERE 1 = 1
			
		@IF !empty(:limit) 
		THEN			
			-- cursor pagination
			@IF isset(:cursor)
			THEN
				AND region_group.region_group_id > :cursor
			END @IF
			LIMIT :limit OFFSET :start
		END @IF
		
		;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(region_group.region_group_id, region_group) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	
	-- get regions for region group 

	CREATE PROCEDURE getRegions(
		IN region_group_id INT,
		IN country_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one
	)
	BEGIN
		-- region
		SELECT regions.*,country.name as country, region.name as region
			FROM region_to_region_group AS regions 
		LEFT JOIN region ON regions.region_id = region.region_id
		LEFT JOIN country ON regions.country_id = country.country_id
		
		WHERE regions.region_group_id = :region_group_id
		
		
		@IF !empty(:country_id) 
		THEN			
			AND region.country_id = :country_id
		END @IF		
		
		@IF !empty(:limit) 
		THEN			
			LIMIT :limit OFFSET :start
		END @IF
		;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(region.region_id, region) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
	END	

	-- check region for region group 

	CREATE PROCEDURE isRegion(
		IN region_group_id INT,
		IN country_id INT,
		IN region_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one
	)
	BEGIN
		-- region
		SELECT *
			FROM region_to_region_group AS regions 
		
		WHERE 1 = 1
		
		
		@IF !empty(:region_group_id) 
		THEN			
			AND regions.region_group_id = :region_group_id
		END @IF			

		@IF !empty(:country_id) 
		THEN			
			AND regions.country_id = :country_id
		END @IF			
		
		@IF !empty(:region_id) 
		THEN			
			AND (regions.region_id = :region_id OR regions.region_id = 0)
		END @IF		
		
		@IF !empty(:limit) 
		THEN			
			LIMIT :limit OFFSET :start
		END @IF
		;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(regions.region_id, regions) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
	END	

	-- add region_group

	CREATE PROCEDURE addRegions(
		IN region_to_region_group ROWS,
		IN region_group_id INT,
		OUT insert_id,
		OUT insert_id
	)
	BEGIN
		-- BEGIN transaction;

		DELETE FROM region_to_region_group WHERE region_group_id = :region_group_id;
		
		-- allow only table fields and set defaults for missing values
		:region_to_region_group_data  = @FILTER(:region_to_region_group, region_to_region_group)
		
		
		@EACH(:region_to_region_group_data) 
		INSERT INTO region_to_region_group 
			
			( @KEYS(:each), region_group_id )
			
	  	VALUES ( :each, :region_group_id );
		
		-- END transaction;

	END

	-- get region_group

	CREATE PROCEDURE get(
		IN region_group_id INT,
		OUT fetch_row 
	)
	BEGIN
		-- region_group
		SELECT *
			FROM region_group as _ WHERE region_group_id = :region_group_id;
	END
	
	-- add region group

	CREATE PROCEDURE add(
		IN region_group ROW,
		OUT fetch_one
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:region_group_data  = @FILTER(:region_group, region_group)
		
		
		INSERT INTO region_group 
			
			( @KEYS(:region_group_data) )
			
	  	VALUES ( :region_group_data ) RETURNING region_id;

	END
	
	-- edit region group

	CREATE PROCEDURE edit(
		IN region_group ROW,
		IN region_group_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:region_group, region_group)

		UPDATE region_group
			
			SET @LIST(:region_group) 
			
		WHERE region_group_id = :region_group_id


	END
	
	-- delete region_group

	CREATE PROCEDURE delete(
		IN region_group_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows 
	)
	BEGIN
		-- region
		DELETE FROM region_to_region_group WHERE region_group_id IN (:region_group_id);
		DELETE FROM region_group WHERE region_group_id IN (:region_group_id);
	END
