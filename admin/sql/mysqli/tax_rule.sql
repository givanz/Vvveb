-- Tax rules

	-- get all tax rules

	PROCEDURE getAll(
		IN tax_type_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all, 
		OUT fetch_one,
	)
	BEGIN
		-- tax_rule
		SELECT *
			FROM tax_rule AS tax_rule WHERE 1 = 1

			
		@IF !empty(:tax_type_id) 
		THEN			
			AND tax_type_id = :tax_type_id
		END @IF		
		
		@IF !empty(:limit) 
		THEN			
			@SQL_LIMIT(:start, :limit)
		END @IF
		
		ORDER BY priority;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(tax_rule.tax_rule_id, tax_rule) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;		
			
	END	
	
	-- get tax rule

	PROCEDURE get(
		IN tax_rule_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- tax_rule
		SELECT *
			FROM tax_rule as _ WHERE tax_rule_id = :tax_rule_id;
	END
	
	-- add tax rule

	PROCEDURE add(
		IN tax_rule ARRAY,
		IN tax_type_id INT,
		OUT insert_id
	)
	BEGIN
		-- BEGIN transaction;

		DELETE FROM tax_rule WHERE tax_type_id = :tax_type_id;
		
		-- allow only table fields and set defaults for missing values
		:tax_rule_data  = @FILTER(:tax_rule, tax_rule);
		
		
		@EACH(:tax_rule_data) 
		INSERT INTO tax_rule 
			
			( @KEYS(:each), tax_type_id )
			
	  	VALUES ( :each, :tax_type_id );
		
		-- END transaction;

	END
	
	-- edit tax rule
	
	CREATE PROCEDURE edit(
		IN tax_rule ARRAY,
		IN tax_rule_id INT,
		OUT affected_rows
	)
	BEGIN

		-- allow only table fields and set defaults for missing values
		@FILTER(:tax_rule, tax_rule);

		UPDATE tax_rule
			
			SET @LIST(:tax_rule) 
			
		WHERE tax_rule_id = :tax_rule_id


	END
	
