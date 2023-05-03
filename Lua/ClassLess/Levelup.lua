local function OnLevel(event, player)
local level=player:GetLevel()
if level>=10 then if not player:HasSkill(414) then player:SetSkill(414, 0, 1, 1) end end
if level>=20 then if not player:HasSkill(413) then player:SetSkill(413, 0, 1, 1) end end
if level>=40 then if not player:HasSkill(293) then player:SetSkill(293, 0, 1, 1) end end
end

RegisterPlayerEvent(13, OnLevel)

local plrs = GetPlayersInWorld()
if plrs then
    for i, player in ipairs(plrs) do
        OnLevel(i, player)
    end
end