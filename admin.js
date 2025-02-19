
import mysql from 'mysql2';
export const con = mysql.createConnection({
    host: "127.0.0.1",
    user: "fcs2",
    password:"fcs123",
    database: "user_management"
})

con.connect(function(error) {
    con.query("select * from USERS", function(error, result){
        if(error) throw error;
        console.log(result);
    })
})

let pending_reg = [];

con.query("select * from pending_registrations", function(error, result){
    if(error) throw error;
    pending_reg = result;
    console.log("this is from the pending user");
    console.log(result);
})

console.log("this is from pending reg ");
console.log(pending_reg);

