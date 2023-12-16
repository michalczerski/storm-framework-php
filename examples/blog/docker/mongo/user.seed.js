const db = connect( 'mongodb://localhost/blog');

db.users.insertOne({name: "Admin", password: "Admin"});