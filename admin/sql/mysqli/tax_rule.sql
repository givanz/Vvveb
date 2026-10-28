-- Tax rules

	-- get all tax rules

	CREATE PROCEDURE getAll(
		IN tax_type_id INT,
		IN start INT,
		IN limit INT,
		IN cursor INT, -- current cursor (tax_rule_id) for cursor pagination
		OUT fetch_all, 
		OUT fetch_one
	)
	BEGIN
		-- tax_rule
		SELECT *
			FROM tax_rule WHERE 1 = 1

			
		@IF !empty(:tax_type_id) 
		THEN			
			AND tax_type_id = :tax_type_id
		END @IF		
		
		-- cursor pagination
		
		@IF isset(:cursor)
		
		THEN
		
			AND tax_rule.tax_rule_id > :cursor
		
		END @IF
		
		ORDER BY priority
		
		@IF !empty(:limit) 
		THEN			
			LIMIT :limit OFFSET :start
		END @IF;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(tax_rule.tax_rule_id, tax_rule) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get tax rule

	CREATE PROCEDURE get(
		IN tax_rule_id INT,
		OUT fetch_row 
	)
	BEGIN
		-- tax_rule
		SELECT *
			FROM tax_rule as _ WHERE tax_rule_id = :tax_rule_id;
	END
	
	-- add tax rule

	CREATE PROCEDURE add(
		IN tax_rule ROWS,
		IN tax_type_id INT,
		OUT insert_id,
		OUT insert_id
	)
	BEGIN
		-- BEGIN transaction;

		DELETE FROM tax_rule WHERE tax_type_id = :tax_type_id;
		
		-- allow only table fields and set defaults for missing values
		:tax_rule_data  = @FILTER(:tax_rule, tax_rule)
		
		
		@EACH(:tax_rule_data) 
		INSERT INTO tax_rule 
			
			( @KEYS(:each), tax_type_id )
			
	  	VALUES ( :each, :tax_type_id );
		
		-- END transaction;

	END
	
	-- edit tax rule
	
	CREATE PROCEDURE edit(
		IN tax_rule ROW,
		IN tax_rule_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:tax_rule, tax_rule)

		UPDATE tax_rule
			
			SET @LIST(:tax_rule) 
			
		WHERE tax_rule_id = :tax_rule_id


	END
	
