-- Tax type

	-- get all tax types

	CREATE PROCEDURE getAll(
		IN start INT,
		IN limit INT,
		IN cursor INT, -- current cursor (tax_type_id) for cursor pagination
		OUT fetch_all, 
		OUT fetch_one
	)
	BEGIN
		-- tax_type
		SELECT *
			FROM tax_type WHERE 1 = 1
			
		
		-- cursor pagination
			
		
		@IF isset(:cursor)
			
		
		THEN
			
		
			AND tax_type.tax_type_id > :cursor
			
		
		END @IF
			
		
		LIMIT :limit OFFSET :start;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(tax_type.tax_type_id, tax_type) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get tax type

	CREATE PROCEDURE get(
		IN tax_type_id INT,
		OUT fetch_row 
	)
	BEGIN
		-- tax_type
		SELECT *
			FROM tax_type as _ WHERE tax_type_id = :tax_type_id;
	END
	
	-- get tax class and tax rules for region

	CREATE PROCEDURE getRegionRules(
		IN country_id INT,
		IN region_id INT,
		IN based CHAR,
		IN user_group_id INT,
		OUT fetch_all 
	)
	BEGIN
	
		SELECT tax_rule.tax_type_id, tax_rate.tax_rate_id, tax_rate.name, tax_rate.rate, tax_rate.type, tax_rule.priority 
			FROM tax_rule
				LEFT JOIN tax_rate ON (tax_rule.tax_rate_id = tax_rate.tax_rate_id) 
				LEFT JOIN region_to_region_group ON (tax_rate.region_group_id = region_to_region_group.region_group_id) 
				LEFT JOIN region_group ON (tax_rate.region_group_id = region_group.region_group_id) 
				LEFT JOIN tax_rate_to_user_group AS rate_group ON (tax_rate.tax_rate_id = rate_group.tax_rate_id)
		WHERE tax_rule.based = :based  AND region_to_region_group.country_id = :country_id AND (region_to_region_group.region_id = '0' OR region_to_region_group.region_id = :region_id) 
		@IF isset(:user_group_id)
		THEN
			AND (rate_group.tax_rate_id IS NULL OR rate_group.user_group_id = :user_group_id)
		END @IF
			ORDER BY tax_rule.priority ASC
	
	END	
	
	-- delete tax type

	CREATE PROCEDURE delete(
		IN tax_type_id ARRAY,
		OUT affected_rows, 
		OUT affected_rows 
	)
	BEGIN
		-- tax_rules
		DELETE FROM tax_rule WHERE tax_type_id IN (:tax_type_id);

		-- tax_type
		DELETE FROM tax_type WHERE tax_type_id IN (:tax_type_id);
	END
	
	-- add tax_type

	CREATE PROCEDURE add(
		IN tax_type ROW,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:tax_type_data  = @FILTER(:tax_type, tax_type)
		
		
		INSERT INTO tax_type 
			
			( @KEYS(:tax_type_data) )
			
	  	VALUES ( :tax_type_data );

	END
	
	-- edit tax_type
	CREATE PROCEDURE edit(
		IN tax_type ROW,
		IN tax_type_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:tax_type, tax_type)

		UPDATE tax_type
			
			SET @LIST(:tax_type) 
			
		WHERE tax_type_id = :tax_type_id


	END
	
