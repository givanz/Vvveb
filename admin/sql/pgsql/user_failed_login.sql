-- user_failed_login

	-- get all entries

	CREATE PROCEDURE getAll(
		IN start INT,
		IN limit INT,
		IN user_id INT,
		IN count INT,
		IN updated_at CHAR,
		
		-- return array of user_failed_login 
		OUT fetch_all,
		-- return user_failed_login count for count query
		OUT fetch_one
	)
	BEGIN
        
        SELECT * FROM user_failed_login WHERE 1 = 1 

        	@IF isset(:user_id) AND !empty(:user_id)
			THEN 
				AND user_failed_login.user_id = :user_id 
        	END @IF	

			@IF isset(:count) AND !empty(:count)
			THEN 
				AND user_failed_login.count > :count
        	END @IF				
			
			@IF isset(:updated_at) AND !empty(:updated_at)
			THEN 
				AND user_failed_login.updated_at = :updated_at
        	END @IF	

			ORDER BY user_failed_login.user_id, user_failed_login.updated_at
			
			-- limit
			@IF isset(:limit)
			THEN
				@SQL_LIMIT(:start, :limit)
			END @IF;		

		-- SELECT FOUND_ROWS() as count;
		SELECT count(*) FROM (
			
			@SQL_COUNT(user_failed_login.user_id, user) -- this takes previous query removes limit and replaces select columns with parameter user_id
			
		) as count;				
        
    END

	-- get user information

	CREATE PROCEDURE get(
		IN user_id INT,
		IN updated_at CHAR,
		IN count INT,
		IN username CHAR,
		IN email CHAR,
		IN status INT,
		IN role_id INT,
		
		OUT fetch_row
	)
	BEGIN
        
        SELECT _.* FROM user_failed_login AS _ 
			LEFT JOIN "user" ON ("user".user_id = _.user_id)
		
		WHERE 1 = 1


		@IF isset(:user_id) AND !empty(:user_id)
		THEN 
			AND _.user_id = :user_id 
		END @IF	

		@IF isset(:count) AND !empty(:count)
		THEN 
			AND _.count > :count
		END @IF				
		
		@IF isset(:updated_at) AND !empty(:updated_at)
		THEN 
			AND _.updated_at = :updated_at
		END @IF	
		
		@IF isset(:username)
		THEN 
			AND "user".username = :username 
		END @IF	

		@IF isset(:email)
		THEN 
			AND "user".email = :email 
		END @IF			

		@IF isset(:status)
		THEN 
			AND "user".status = :status 
		END @IF	            
			
		@IF isset(:role_id)
		THEN 
			AND "user".role_id = :role_id 
		END @IF		
		
		LIMIT 1;
        
    END
    
    

	-- Add new failed attempt

	CREATE PROCEDURE logFailed(
		IN user_id INT,
		IN username CHAR,
		IN updated_at CHAR,
		IN last_ip INT,
		OUT fetch_one,
		OUT insert_id
	)
	BEGIN
	
		SELECT user_id FROM "user" WHERE "user".status = 1

		@IF isset(:user_id) AND !empty(:user_id)
		THEN 
			AND user_id = :user_id 
		END @IF	

		@IF isset(:username)
		THEN 
			AND username = :username 
		END @IF	

		@IF isset(:email)
		THEN 
			AND email = :email 
		END @IF			

		@IF isset(:user_id)
		THEN 
			AND user_id = :user_id 
		END @IF			

		LIMIT 1;	
		
		@IF isset(@result.user)
		THEN 
		
			INSERT INTO user_failed_login 
				
				( "user_id", "updated_at", "last_ip")
				
			VALUES ( @result.user, :updated_at, :last_ip )	 
			
			ON CONFLICT ("user_id", "updated_at") DO UPDATE SET count = user_failed_login.count + 1, "last_ip" = :last_ip
			
		END @IF;	
		
	END   	
	
	-- Add new user_failed_login

	CREATE PROCEDURE add(
		IN user_failed_login ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		@FILTER(:user_failed_login, user_failed_login)
		
		INSERT INTO user_failed_login 
			
			( @KEYS(:user_failed_login) )
			
	  	VALUES ( :user_failed_login )	 
	END    
    
	
	-- Update user_failed_login 
	
	CREATE PROCEDURE edit(
		IN user_id INT,
		IN user_failed_login ARRAY,
		OUT affected_rows
	)
	BEGIN
		-- allow only table fields and set defaults for missing values
		@FILTER(:user_failed_login, user_failed_login)

		UPDATE user_failed_login 
			
			SET @LIST(:user_failed_login) 
			
		WHERE user_id = :user_id;

	END

	-- delete user_failed_login

	PROCEDURE delete(
		IN user_id ARRAY,
		IN updated_at CHAR,
		IN count INT,

		OUT affected_rows
	)
	BEGIN

		DELETE user_failed_login FROM user_failed_login 
			INNER JOIN user.user_id	ON user = user_failed_login.user_id
		WHERE 

		@IF isset(:user_id) AND !empty(:user_id)
		THEN 
			AND user_failed_login.user_id = :user_id 
		END @IF	

		@IF isset(:username)
		THEN 
			AND user.username = :username 
		END @IF	

		@IF isset(:email)
		THEN 
			AND user.email = :email 
		END @IF			

		@IF isset(:user_id)
		THEN 
			AND user.user_id IN (:user_id) 
		END @IF	
		
	END	
