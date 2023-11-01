-- Admin

	-- get all entries

	CREATE PROCEDURE getAll(
		IN start INT,
		IN limit INT,
        IN status INT,
		IN search CHAR,
		IN email CHAR,
		IN phone_number CHAR,
		
		-- return array of admin 
		OUT fetch_all,
		-- return admin count for count query
		OUT fetch_one
	)
	BEGIN
        
        SELECT * FROM admin AS admin WHERE 1 = 1 


            @IF isset(:status) AND !empty(:status)
			THEN 
				AND admin.status = :status 
        	END @IF	

			@IF isset(:email) AND !empty(:email)
			THEN 
				AND admin.email = :email 
        	END @IF	

			@IF isset(:phone_number) AND !empty(:phone_number)
			THEN 
				AND admin.phone_number = :phone_number 
        	END @IF	

            -- search
            @IF isset(:search) AND !empty(:search)
			THEN 
				AND admin.username LIKE CONCAT('%',:search,'%') || admin.first_name LIKE CONCAT('%',:search,'%') || admin.last_name LIKE CONCAT('%',:search,'%')
        	END @IF	       
            
			
			-- limit
			@IF isset(:limit)
			THEN
				@SQL_LIMIT(:start, :limit)
			END @IF;		

		-- SELECT FOUND_ROWS() as count;
		SELECT count(*) FROM (
			
			@SQL_COUNT(admin.user_id, user) -- this takes previous query removes limit and replaces select columns with parameter user_id
			
		) as count;				
        
    END

	-- get user information

	CREATE PROCEDURE get(
		IN username CHAR,
		IN email CHAR,
		IN token CHAR,
	        IN admin_id INT,
	        IN status INT,
	        IN role_id INT,
		OUT fetch_row
	)
	BEGIN
        
        SELECT _.*, role.permissions FROM admin AS _ 
			LEFT JOIN role ON (_.role_id = role.role_id)
		
		WHERE 1 = 1

        @IF isset(:username)
	THEN 
		AND _.username = :username 
       	END @IF	

        @IF isset(:email)
	THEN 
		AND _.email = :email 
        END @IF			

        @IF isset(:admin_id)
		THEN 
			AND _.admin_id = :admin_id 
       	END @IF			

        @IF isset(:status)
		THEN 
			AND _.status = :status 
       	END @IF	            
		
		@IF isset(:token)
		THEN 
			AND _.token = :token 
       	END @IF	
			
       	@IF isset(:role_id)
		THEN 
			AND _.role_id = :role_id 
       	END @IF	
			
		LIMIT 1;
        
    END
    
    

	-- Add new admin

	CREATE PROCEDURE add(
		IN admin ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		@FILTER(:admin, admin);
		
		INSERT INTO admin 
			
			( @KEYS(:admin) )
			
	  	VALUES ( :admin )	 
	END    
    

	-- Update admin 
	
	CREATE PROCEDURE edit(
		IN user CHAR,
		IN email CHAR,
       	IN admin_id INT,
		IN admin ARRAY,
		IN role_id INT,
		OUT affected_rows
	)
	BEGIN
		-- allow only table fields and set defaults for missing values
		@FILTER(:admin, admin);

		UPDATE admin 
			
			SET @LIST(:admin) 
			
		WHERE 

        @IF isset(:email)
		THEN 
			email = :email 
        END @IF			

        @IF isset(:admin_id)
		THEN 
			admin_id = :admin_id 
        END @IF					

		@IF isset(:username)
		THEN 
			username = :username 
       	 END @IF
	END

	-- delete admin

	PROCEDURE delete(
		IN  admin_id ARRAY,
		OUT affected_rows
	)
	BEGIN

		DELETE FROM admin WHERE admin_id IN (:admin_id);
		
	END	
	
	-- set role

	CREATE PROCEDURE setRole(
        IN admin_id INT,
        IN role CHAR,
        IN role_id INT
        OUT insert_id
	)
	BEGIN
		
	
		UPDATE admin 
			
			SET  
            
            @IF isset(:role_id)
			THEN 
				role_id = :role_id 
        	END @IF		


            @IF isset(:role)
			THEN 
				role_id = (SELECT role_id FROM roles WHERE name = :role)
        	END @IF		

			
		WHERE admin_id = :admin_id 
    END
