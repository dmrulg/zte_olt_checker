# zte_olt_checker

First generate key
```
./bin/wca.sh user:generate-key admin 365d
```

and copy key to file worker. Edit server ip if need.

![image](https://github.com/dmrulg/zte_olt_checker/assets/29884923/ed2478ca-f172-4a41-a5ef-71a32388f19c)


Set up ips to ping on Wildcore device Device management -> Devices -> Edit -> Additional parameters 

![image](https://github.com/dmrulg/zte_olt_checker/assets/29884923/7f36956a-a58a-47a6-89b9-2a01af2485e5)

Should be look like:
```
{
  "diag_ips": {
    "epon_1/1/1": [
      "10.10.10.10",
      "10.1.2.1",
      "10.0.1.2",
      "10.4.1.2"
    ]
  }
}
```



Logs -> Actions looks like:

![image](https://github.com/dmrulg/zte_olt_checker/assets/29884923/a1631d51-30aa-418c-ad87-a9d100d08f6e)
