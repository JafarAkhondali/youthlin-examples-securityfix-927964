import "std.y" -> String;
import "std.y" -> System;
int i;
int i0 = 0;
boolean b;
boolean bT = true;
boolean bF = false;
byte by;
byte by1 = 1;
byte byb1 = (byte) 1;
float f;
float f1 = 1;
float f2 = 2f;
float f2F = 2F;
char c;
char c1 = 'c';
String s;
String s1 = "s1";
System.out.println(s1);
s = System.in.readLine();
struct User{
    User(){
    }
    //User1(String name){}
    void User1(String name){}
    void hello(){
        System.out.println(this.toString());
    }
}
User user = User();
user.hello();
