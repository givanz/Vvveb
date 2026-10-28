	-- Roles
	
	CREATE PROCEDURE getAll(
		IN start INT,
		IN limit INT,
		IN cursor INT, -- current cursor (role_id) for cursor pagination
		
		-- return array of roles for roles query
		OUT fetch_all,
		OUT fetch_one
	)
	BEGIN
        
        SELECT * FROM role WHERE 1 = 1 

			-- cursor pagination

			@IF isset(:cursor)

			THEN

			    AND role.role_id > :cursor

			END @IF

			-- limit
			@IF isset(:limit)
			THEN
				LIMIT :limit OFFSET :start
			END @IF;		

		-- SELECT FOUND_ROWS() as count;
		SELECT count(*) FROM (
			
			@SQL_COUNT(roles.user_id, user) -- this takes previous query removes limit and replaces select columns with parameter user_id
			
		) as count;				
        
    END

	-- get role data

	CREATE PROCEDURE get(
		IN role_id INT,
		OUT fetch_row
	)
	BEGIN
        
        SELECT * FROM role AS _ 
			WHERE _.role_id = :role_id 
		LIMIT 1
        
    END

	-- Add new role

	CREATE PROCEDURE add(
		IN role ROW,
		OUT fetch_one
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		@FILTER(:role, role)
		
		INSERT INTO role 
			
			( @KEYS(:role) )
			
	  	VALUES ( :role ) RETURNING role_id;	 
	END    
    

	-- Update role 
	
	CREATE PROCEDURE edit(
        IN role_id INT,
		IN role ROW,
		OUT affected_rows
	)
	BEGIN
		-- allow only table fields and set defaults for missing values
		@FILTER(:role, role)

		UPDATE role 
			
			SET @LIST(:role) 
			
		WHERE role_id = :role_id 			
	END

	-- delete role

	CREATE PROCEDURE delete(
		IN role_id ARRAY,
		OUT affected_rows
	)
	BEGIN
	
		DELETE FROM role WHERE role_id IN (:role_id);
	
	END
