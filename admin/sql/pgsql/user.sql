-- User

	-- get all entries

	CREATE PROCEDURE getAll(
        IN status INT,
		IN search CHAR,
		IN email CHAR,
		IN phone_number CHAR,
		
		-- pagination
		IN start INT,
		IN limit INT,
		IN order_by CHAR,
		IN direction CHAR,

		-- return array of user
		OUT fetch_all,
		-- return user count for count query
		OUT fetch_one
	)
	BEGIN
        
        SELECT * FROM "user" WHERE 1 = 1 


        	@IF isset(:status) AND !empty(:status)
        	THEN 
			AND "user".status = :status 
        	END @IF	
			
        	@IF isset(:email) AND !empty(:email)
		THEN 
			AND "user".email = :email 
        	END @IF	

        	@IF isset(:phone_number) AND !empty(:phone_number)
        	THEN 
			AND "user".phone_number = :phone_number 
        	END @IF	

            -- search
            @IF isset(:search) AND !empty(:search)
			THEN 
				AND "user".username LIKE '%' || :search || '%' OR 
				"user".first_name LIKE '%' || :search || '%' OR 
				"user".last_name LIKE '%' || :search || '%'
        	END @IF	     
            
			-- ORDER BY parameters can't be binded, because they are added to the query directly they must be properly sanitized by only allowing a predefined set of values
			@IF isset(:order_by)
			THEN
				ORDER BY "user".$order_by $direction		
			@ELSE
				ORDER BY "user".user_id DESC
			END @IF
			
			-- limit
			@IF isset(:limit)
			THEN
				@SQL_LIMIT(:start, :limit)
			END @IF;		

		-- SELECT FOUND_ROWS() as count;
		SELECT count(*) FROM (
			
			@SQL_COUNT("user".user_id, user) -- this takes previous query removes limit and replaces select columns with parameter user_id
			
		) as count;				
        
    END
	
	-- get user information

	CREATE PROCEDURE get(
		IN username CHAR,
		IN email CHAR,
        IN user_id INT,
        IN status INT,
		OUT fetch_row,
	)
	BEGIN
        
        SELECT * FROM "user" AS _ WHERE 1 = 1 

        	@IF isset(:username) && !isset(:email)
        	THEN 
			AND _.username = :username 
        	END @IF	

        	@IF isset(:email) && !isset(:username)
        	THEN 
			AND _.email = :email 
        	END @IF			

        	@IF isset(:email) && isset(:username)
        	THEN 
			AND _.email = :email OR _.username = :username
        	END @IF			

        	@IF isset(:user_id)
        	THEN 
			AND _.user_id = :user_id 
        	END @IF			

        	@IF isset(:status)
		THEN 
			AND _.status = :status 
        	END @IF	
			
		@IF isset(:token)
		THEN 
			AND _.token = :token 
        	END @IF				
			
		LIMIT 1;
        
    END
    

	-- Add new user

	CREATE PROCEDURE add(
		IN user ARRAY,
		OUT fetch_one
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		@FILTER(:user, user)
		
		INSERT INTO "user" 
			
			( @KEYS(:user) )
			
	  	VALUES ( :user ) RETURNING user_id;	 
	END    
    

	-- Edit comment

	CREATE PROCEDURE edit(
		IN username CHAR,
		IN email CHAR,
        IN user_id INT,
		IN user ARRAY,
		OUT affected_rows
	)
	BEGIN
		-- allow only table fields and set defaults for missing values
		@FILTER(:user, user)

		UPDATE "user" 
			
			SET @LIST(:user) 
			
		WHERE 

        	@IF isset(:email)
			THEN 
				email = :email 
        	END @IF			

        	@IF isset(:user_id)
			THEN 
				user_id = :user_id 
        	END @IF					
			
			@IF isset(:username)
			THEN 
				username = :username 
			END @IF
	END

	-- delete user

	PROCEDURE delete(
		IN  user_id ARRAY,
		OUT affected_rows
	)
	BEGIN

		DELETE FROM "user" WHERE user_id IN (:user_id);
		
	END	
	
	-- Update role

	CREATE PROCEDURE setRole(
        IN user_id INT,
        IN role CHAR,
        IN role_id INT
        OUT insert_id
	)
	BEGIN
		
	
		UPDATE "user" 
			
			SET  
            
            @IF isset(:role_id)
			THEN 
				role_id = :role_id 
        	END @IF		


            @IF isset(:role)
			THEN 
				role_id = (SELECT role_id FROM roles WHERE name = :role)
        	END @IF		

			
		WHERE user_id = :user_id 
    END
