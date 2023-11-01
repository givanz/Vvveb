-- Account


	CREATE PROCEDURE getAll(
		IN start INT,
		IN limit INT,
        IN status INT,
		IN search CHAR,
		IN post_id ARRAY,
		
		-- return array of roles for roles query
		OUT fetch_all,
		-- return roles count for count query
		OUT fetch_one,	)
	BEGIN
        
        SELECT * FROM role WHERE 1 = 1 

			-- limit
			@IF isset(:limit)
			THEN
				@SQL_LIMIT(:start, :limit)
			END @IF;		

		-- SELECT FOUND_ROWS() as count;
		SELECT count(*) FROM (
			
			@SQL_COUNT(roles.user_id, user) -- this takes previous query removes limit and replaces select columns with parameter user_id
			
		) as count;				
        
    END

	-- check user information

	CREATE PROCEDURE get(
        IN role_id INT,
		OUT fetch_row,
	)
	BEGIN
        
        SELECT * FROM role AS _ 
			WHERE _.role_id = :role_id 
		LIMIT 1
        
    END
    
    

	-- Add new role

	CREATE PROCEDURE add(
		IN role ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		@FILTER(:role, role);
		
		INSERT INTO role 
			
			( @KEYS(:role) )
			
	  	VALUES ( :role )	 
	END    
    

	-- Update role 
	
	CREATE PROCEDURE edit(
        IN role_id INT,
		IN role ARRAY,
		OUT affected_rows
	)
	BEGIN
		-- allow only table fields and set defaults for missing values
		@FILTER(:role, role);

		UPDATE role 
			
			SET @LIST(:role) 
			
		WHERE role_id = :role_id 			
	END

-- Add new role

	CREATE PROCEDURE setRole(
        IN role_id INT,
        IN role CHAR,
        IN role_id INT
        OUT insert_id
	)
	BEGIN
		
	
		UPDATE role 
			
			SET  
            
            @IF isset(:role_id)
			THEN 
				role_id = :role_id 
        	END @IF		


            @IF isset(:role)
			THEN 
				role_id = (SELECT role_id FROM roles WHERE name = :role)
        	END @IF		

			
		WHERE role_id = :role_id 
    END
